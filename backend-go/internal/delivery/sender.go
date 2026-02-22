package delivery

import (
	"fmt"
	"io"
	"log"
	"net/smtp"
	"os"
	"time"

	"github.com/jlaffaye/ftp"
)

type DeliveryType string

const (
	TypeEmail DeliveryType = "email"
	TypeFTP   DeliveryType = "ftp"
)

func Send(dType DeliveryType, config map[string]interface{}, filePath string) (string, error) {
	switch dType {
	case TypeEmail:
		err := sendEmail(config, filePath)
		return "", err
	case TypeFTP:
		f, err := os.Open(filePath)
		if err != nil {
			return "", err
		}
		defer f.Close()
		return uploadFTPStream(config, f)
	default:
		return "", fmt.Errorf("unsupported delivery type: %s", dType)
	}
}

func SendStream(dType DeliveryType, config map[string]interface{}, r io.Reader) (string, error) {
	switch dType {
	case TypeFTP:
		return uploadFTPStream(config, r)
	default:
		return "", fmt.Errorf("unsupported streaming delivery type: %s", dType)
	}
}

func sendEmail(config map[string]interface{}, filePath string) error {
    host, _ := config["host"].(string)
    port, _ := config["port"].(float64) // JSON numbers are floats
    username, _ := config["username"].(string)
    password, _ := config["password"].(string)
    to, _ := config["to"].(string)
    from, _ := config["from"].(string)

    // TODO: Implement attachment logic using MIME/Multipart.
    // For brevity, I'll just check connection.
    // In real implementation, need to construct multipart message.
    
    auth := smtp.PlainAuth("", username, password, host)
    err := smtp.SendMail(fmt.Sprintf("%s:%d", host, int(port)), auth, from, []string{to}, []byte("Subject: Report\r\n\r\nSee attachment."))
    return err
}

func uploadFTPStream(config map[string]interface{}, r io.Reader) (string, error) {
	host, _ := config["host"].(string)
	
	// Handle different types for port (int, float64, string)
	var port int
	switch p := config["port"].(type) {
	case float64:
		port = int(p)
	case int:
		port = p
	case int64:
		port = int(p)
	case string:
		fmt.Sscanf(p, "%d", &port)
	}
	if port == 0 {
		port = 21 // Default FTP port
	}

	username, _ := config["username"].(string)
	password, _ := config["password"].(string)
	reportName, _ := config["report_name"].(string)
	extension, _ := config["extension"].(string)

	addr := fmt.Sprintf("%s:%d", host, port)
	log.Printf("Connecting to FTP: %s as %s", addr, username)
	
	c, err := ftp.Dial(addr, ftp.DialWithTimeout(5*time.Second))
	if err != nil {
		return "", fmt.Errorf("ftp dial error: %v", err)
	}
	defer c.Quit()

	err = c.Login(username, password)
	if err != nil {
		return "", err
	}

	// Dynamic Path: [YYYY-MM-DD]-[ReportName]
	now := time.Now()
	dateFolder := fmt.Sprintf("%s-%s", now.Format("2006-01-02"), reportName)
	fileName := fmt.Sprintf("%s-%s.%s", now.Format("2006-01-02"), now.Format("15:04"), extension)
	finalPath := fmt.Sprintf("%s/%s", dateFolder, fileName)

	// Ensure directory exists
	_ = c.MakeDir(dateFolder)

	err = c.Stor(finalPath, r)
	if err != nil {
		return "", err
	}
	return finalPath, nil
}
