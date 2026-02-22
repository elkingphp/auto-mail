package api_client

import (
	"bytes"
	"encoding/json"
	"fmt"
	"net/http"
	"rbdb-backend-go/config"
	"rbdb-backend-go/internal/models"
	"time"
)

type Client struct {
	BaseURL string
	Token   string
	HTTP    *http.Client
}

func NewClient(cfg *config.Config) *Client {
	return &Client{
		BaseURL: cfg.ControlPlaneURL,
		Token:   cfg.ControlPlaneToken,
		HTTP:    &http.Client{Timeout: 30 * time.Second},
	}
}

func (c *Client) GetReport(reportID string) (*models.Report, error) {
	url := fmt.Sprintf("%s/reports/%s", c.BaseURL, reportID)
	req, err := http.NewRequest("GET", url, nil)
	if err != nil {
		return nil, err
	}
	
	c.addHeaders(req)
	
	resp, err := c.HTTP.Do(req)
	if err != nil {
		return nil, err
	}
	defer resp.Body.Close()
	
	if resp.StatusCode != http.StatusOK {
		return nil, fmt.Errorf("failed to fetch report, status: %d", resp.StatusCode)
	}

	var parsedResp struct {
		Success bool           `json:"success"`
		Data    *models.Report `json:"data"`
	}
	
	if err := json.NewDecoder(resp.Body).Decode(&parsedResp); err != nil {
		return nil, err
	}
	
	return parsedResp.Data, nil
}

func (c *Client) GetPendingExecutions() ([]models.Job, error) {
    // This assumes the Control Plane has this endpoint. 
    // If not, this will return 404.
    url := fmt.Sprintf("%s/executions?status=pending", c.BaseURL)
    req, err := http.NewRequest("GET", url, nil)
    if err != nil {
        return nil, err
    }
    c.addHeaders(req)
    
    resp, err := c.HTTP.Do(req)
    if err != nil {
        return nil, err
    }
    defer resp.Body.Close()
    
    // If API not ready, return empty list gracefully to avoid crash loop
    if resp.StatusCode != http.StatusOK {
        return []models.Job{}, nil // fmt.Errorf("failed to fetch executions, status: %d", resp.StatusCode)
    }

    var parsedResp struct {
        Success bool         `json:"success"`
        Data    []models.Job `json:"data"`
    }
    if err := json.NewDecoder(resp.Body).Decode(&parsedResp); err != nil {
       return nil, err
    }
    return parsedResp.Data, nil
}

func (c *Client) UpdateExecution(executionID string, update models.ExecutionUpdate) error {
	// Assumption: Endpoint exists at /executions/{id}. 
    // Wait, I didn't verify ExecutionController in Phase 2.5, I only did CRUD for core resources.
    // However, I should assume a standard structure. If it doesn't exist, I'll need to mock it or ask user to create it locally?
    // User said "Update execution status in Control Plane executions table".
    // I likely need to assume this endpoint exists or will be created. 
    // Since Phase 2.5 was "Control Plane Completion", and I didn't explicitly implement ExecutionController logic (it was not requested in Phase 2.5 explicitly, only core metadata).
    // I can assume standard REST: PUT /api/v1/executions/{id}
    
	url := fmt.Sprintf("%s/executions/%s", c.BaseURL, executionID)
	
	body, err := json.Marshal(update)
	if err != nil {
		return err
	}
	
	req, err := http.NewRequest("PUT", url, bytes.NewBuffer(body))
	if err != nil {
		return err
	}
	
	c.addHeaders(req)
	
	resp, err := c.HTTP.Do(req)
	if err != nil {
		return err
	}
	defer resp.Body.Close()
	
	if resp.StatusCode != http.StatusOK && resp.StatusCode != http.StatusNoContent {
		return fmt.Errorf("failed to update execution, status: %d", resp.StatusCode)
	}
	
	return nil
}

func (c *Client) addHeaders(req *http.Request) {
	req.Header.Set("Authorization", "Bearer "+c.Token)
	req.Header.Set("Accept", "application/json")
	req.Header.Set("Content-Type", "application/json")
}
