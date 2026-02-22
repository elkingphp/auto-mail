//go:build integration
package executor

import (
	"context"
	"encoding/json"
	"fmt"
	"rbdb-backend-go/config"
	"rbdb-backend-go/internal/api_client"
	"rbdb-backend-go/internal/models"
	"testing"

	"github.com/redis/go-redis/v9"
)

func TestEngineDeepIntegration(t *testing.T) {
	cfg := config.Load()
    
    t.Logf("Using Control Plane: %s", cfg.ControlPlaneURL)
    t.Logf("Using Redis: %s:%s", cfg.RedisHost, cfg.RedisPort)

	// Use the Execution ID from our manual setup or a previous run
	// In a real CI, we would fetch this dynamically.
	executionID := "019c615c-c11e-7257-ad24-4fab5a957a87"
	reportID := "019c615c-c110-7323-a3fb-b5c4cf22f387"

	job := models.Job{
		JobID:          "test-job-integration",
		ExecutionID:    executionID,
		ReportID:       reportID,
		TaskType:       "execute",
		Priority:       "medium",
		TimeoutSeconds: 60,
	}

	// 1. Push to Redis
	rdb := redis.NewClient(&redis.Options{
		Addr: fmt.Sprintf("%s:%s", cfg.RedisHost, cfg.RedisPort),
	})
	payload, _ := json.Marshal(job)
	err := rdb.RPush(context.Background(), "rbdb_execution_queue", payload).Err()
	if err != nil {
		t.Fatalf("Failed to push to Redis: %v", err)
	}

	// 2. Process directly
	client := api_client.NewClient(cfg)
	pool := NewPool(cfg, client)
	
	t.Logf("Starting processing for Execution %s", executionID)
	pool.process(job)
	t.Log("Processing finished")

	// 3. Status Check (via API Client)
	report, err := client.GetReport(reportID)
	if err != nil {
		t.Fatalf("Failed to fetch report back: %v", err)
	}
	t.Logf("Report Name: %s", report.Name)
}
