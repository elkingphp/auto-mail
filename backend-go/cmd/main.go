package main

import (
	"context"
	"encoding/json"
	"fmt"
	"log"
	"os"
	"os/signal"
	"rbdb-backend-go/config"
	"rbdb-backend-go/internal/api_client"
	"rbdb-backend-go/internal/executor"
	"rbdb-backend-go/internal/models"
	"syscall"
	"time"

	"github.com/redis/go-redis/v9"
)

func main() {
	log.Println("Starting RBDB Execution Engine (Redis-Driven)...")

	cfg := config.Load()
	log.Printf("Environment: %s, Workers: %d, Redis: %s:%s", 
		cfg.Environment, cfg.WorkerCount, cfg.RedisHost, cfg.RedisPort)

	client := api_client.NewClient(cfg)
	pool := executor.NewPool(cfg, client)
	pool.Start()

	// Redis client
	rdb := redis.NewClient(&redis.Options{
		Addr: fmt.Sprintf("%s:%s", cfg.RedisHost, cfg.RedisPort),
	})

	ctx := context.Background()

	// Handle graceful shutdown
	stop := make(chan os.Signal, 1)
	signal.Notify(stop, syscall.SIGINT, syscall.SIGTERM)

	// Redis Consumer Loop
	go func() {
		log.Println("Listening for manual pulse executions in Redis...")
		for {
			// BLPop blocks until a job is available
			result, err := rdb.BLPop(ctx, 0, "rbdb_execution_queue").Result()
			if err != nil {
				log.Printf("Redis BLPop error: %v", err)
				time.Sleep(2 * time.Second)
				continue
			}

			// index 0 is the key name, index 1 is the value
			payload := result[1]
			var job models.Job
			if err := json.Unmarshal([]byte(payload), &job); err != nil {
				log.Printf("Payload Parse Error: %v (Payload: %s)", err, payload)
				continue
			}

			log.Printf("Pulse received: Execution %s for Report %s", job.ExecutionID, job.ReportID)
			pool.AddJob(job)
		}
	}()

	<-stop
	log.Println("Engine shutting down...")
}
