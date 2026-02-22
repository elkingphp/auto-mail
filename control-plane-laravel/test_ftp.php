<?php
$conn = ftp_connect('ftp', 21, 5);
if (!$conn) {
    echo "Connection failed\n";
    exit(1);
}
echo "Connected to FTP server\n";

if (!ftp_login($conn, 'ftpuser', 'ftppass')) {
    echo "Login failed\n";
    exit(1);
}
echo "Login successful\n";

ftp_pasv($conn, true);
$files = ftp_nlist($conn, '.');
echo "Files: " . print_r($files, true);

ftp_close($conn);
echo "FTP test complete\n";
