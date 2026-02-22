# Build Stage
FROM golang:1.24-alpine AS builder

WORKDIR /app

# Copy dependency files first
COPY go.mod go.sum ./
RUN go mod download

# Copy the rest of the code
COPY . .
RUN go mod tidy && go build -o main cmd/main.go

# Runtime Stage
FROM alpine:latest

WORKDIR /root/
COPY --from=builder /app/main .

# Install ca-certificates and timezone data
RUN apk --no-cache add ca-certificates tzdata

# Set timezone
ENV TZ=UTC

CMD ["./main"]
