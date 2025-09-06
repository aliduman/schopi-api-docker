#!/bin/bash

# This script prepares the application for deployment to Cloud Run

echo "==== Preparing for Cloud Run deployment ===="

# Step 1: Check file structure
echo "Checking file structure..."
if [ ! -d "src/public" ]; then
  echo "Error: src/public directory not found!"
  exit 1
fi

# Step 2: Ensure startup script is executable
echo "Making startup script executable..."
chmod +x start.sh

# Step 3: Build the Docker image locally for testing
echo "Building Docker image locally..."
docker build -t schopi-api-docker:test .

# Step 4: Run a test container
echo "Starting test container..."
CONTAINER_ID=$(docker run --rm -d -p 8080:8080 -e PORT=8080 schopi-api-docker:test)

# Step 5: Wait for container to start
echo "Waiting for container to start..."
sleep 5

# Step 6: Test the health endpoint
echo "Testing health endpoint..."
curl -s http://localhost:8080/_health || curl -s http://localhost:8080/health.php
curl -s http://localhost:8080/_health
# Step 7: Stop the test container
echo "Stopping test container..."
docker stop $CONTAINER_ID

echo "==== Preparation complete. Ready for deployment ===="
echo "Run gcloud builds submit to deploy to Cloud Run"
