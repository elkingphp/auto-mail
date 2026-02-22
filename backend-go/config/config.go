package config

import (
	"os"
	"strconv"
)

type Config struct {
	ControlPlaneURL   string
	ControlPlaneToken string
	WorkerCount       int
	Environment       string
	RedisHost         string
	RedisPort         string
}

func Load() *Config {
	workers, _ := strconv.Atoi(getEnv("WORKER_COUNT", "5"))

	return &Config{
		ControlPlaneURL:   getEnv("CONTROL_PLANE_URL", "http://localhost:8000/api/v1"),
		ControlPlaneToken: getEnv("CONTROL_PLANE_TOKEN", ""),
		WorkerCount:       workers,
		Environment:       getEnv("APP_ENV", "local"),
		RedisHost:         getEnv("REDIS_HOST", "redis"),
		RedisPort:         getEnv("REDIS_PORT", "6379"),
	}
}

func getEnv(key, fallback string) string {
	if value, ok := os.LookupEnv(key); ok {
		return value
	}
	return fallback
}
