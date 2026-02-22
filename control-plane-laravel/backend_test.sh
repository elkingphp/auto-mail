#!/bin/bash

BASE_URL="http://localhost:8000/api/v1"
TOKEN="34|D4nGj4FcNegkkSEmMv7KkBJnHxm6djVC6Kjc8vvc44f7d467"

echo "--- Backend API Verification Started ---"

# Helper function
call_api() {
  local method=$1
  local endpoint=$2
  local data=$3
  
  if [ -n "$data" ]; then
    curl -s -X $method "$BASE_URL/$endpoint" \
      -H "Authorization: Bearer $TOKEN" \
      -H "Content-Type: application/json" \
      -H "Accept: application/json" \
      -d "$data"
  else
    curl -s -X $method "$BASE_URL/$endpoint" \
      -H "Authorization: Bearer $TOKEN" \
      -H "Accept: application/json"
  fi
}

# 1. Get a Report ID
echo -n "Fetching Reports... "
REPORTS=$(call_api GET "reports")
REPORT_ID=$(echo $REPORTS | grep -o '"id":"[^"]*"' | head -1 | cut -d'"' -f4)

if [ -z "$REPORT_ID" ]; then
  echo "FAIL: No reports found. Cannot test Schedule."
  exit 1
else
  echo "OK (ID: $REPORT_ID)"
fi

# 2. CRUD Email Server
echo -n "Creating Email Server... "
DATA_ES='{"name":"Test Gateway","host":"smtp.example.com","port":587,"username":"test","password":"password","from_address":"test@rbdb.local","is_active":true}'
ES_RES=$(call_api POST "email-servers" "$DATA_ES")
ES_ID=$(echo $ES_RES | grep -o '"id":"[^"]*"' | head -1 | cut -d'"' -f4)

if [ -z "$ES_ID" ]; then
  echo "FAIL: $ES_RES"
else
  echo "OK (ID: $ES_ID)"
fi

# 3. CRUD FTP Server
echo -n "Creating FTP Server... "
DATA_FTP='{"name":"Test FTP","host":"ftp.example.com","port":21,"username":"user","password":"password","root_path":"/","passive_mode":true,"is_active":true}'
FTP_RES=$(call_api POST "ftp-servers" "$DATA_FTP")
FTP_ID=$(echo $FTP_RES | grep -o '"id":"[^"]*"' | head -1 | cut -d'"' -f4)

if [ -z "$FTP_ID" ]; then
  echo "FAIL: $FTP_RES"
else
  echo "OK (ID: $FTP_ID)"
fi

# 4. CRUD Template
echo -n "Creating Template... "
DATA_TMP='{"name":"Test Template","subject":"Test Subject","body_html":"<p>Hello</p>","is_active":true}'
TMP_RES=$(call_api POST "email-templates" "$DATA_TMP")
TMP_ID=$(echo $TMP_RES | grep -o '"id":"[^"]*"' | head -1 | cut -d'"' -f4)

if [ -z "$TMP_ID" ]; then
  echo "FAIL: $TMP_RES"
else
  echo "OK (ID: $TMP_ID)"
fi

# 5. Create Schedule (Email Mode)
echo -n "Creating Schedule (Email Mode)... "
# Note: JSON structure must match Request validation exactly
DATA_SCH_EMAIL="{\"report_id\":\"$REPORT_ID\",\"frequency\":\"Daily\",\"time\":\"08:00:00\",\"is_active\":true,\"delivery_mode\":\"email\",\"email_server_id\":\"$ES_ID\",\"email_template_id\":\"$TMP_ID\",\"recipients\":\"test@rbdb.local\"}"
SCH_RES=$(call_api POST "schedules" "$DATA_SCH_EMAIL")
SCH_ID=$(echo $SCH_RES | grep -o '"id":"[^"]*"' | head -1 | cut -d'"' -f4)

if [ -z "$SCH_ID" ]; then
  echo "FAIL: $SCH_RES"
else
  echo "OK (ID: $SCH_ID)"
fi

# 6. Update Schedule (FTP Mode)
echo -n "Updating Schedule (FTP Mode)... "
DATA_SCH_FTP="{\"report_id\":\"$REPORT_ID\",\"frequency\":\"Daily\",\"time\":\"08:00:00\",\"is_active\":true,\"delivery_mode\":\"ftp\",\"ftp_server_ids\":[\"$FTP_ID\"]}"
SCH_UPD_RES=$(call_api PUT "schedules/$SCH_ID" "$DATA_SCH_FTP")

# Check if response contains the updated mode or success
if echo "$SCH_UPD_RES" | grep -q "ftp"; then
  echo "OK"
else
  echo "FAIL: $SCH_UPD_RES"
fi

# 7. Test Connection (Soft check)
echo -n "Testing Email Connection (Expect Fail on Fake Host)... "
TEST_RES=$(call_api POST "email-servers/test" "{\"id\":\"$ES_ID\"}")
# Connection failed is EXPECTED for fake host
if echo "$TEST_RES" | grep -q "failed"; then
  echo "OK (Correctly reported failure)"
elif echo "$TEST_RES" | grep -q "success"; then
  echo "WARN: Unexpected success?"
else
  echo "OK (API Responded)"
fi

echo "--- Backend API Verification Completed ---"
