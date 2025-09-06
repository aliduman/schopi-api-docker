#!/bin/bash

# This script prepares the application for deployment to Cloud Run

echo "==== Preparing for Cloud Run deployment ===="

# Step 1: Check file structure
echo "Checking file structure..."
if [ ! -d "src/public" ]; then
  echo "Error: src/public directory not found!"
  exit 1
fi

if [ ! -d "src/app" ]; then
  echo "Error: src/app directory not found!"
  exit 1
fi

if [ ! -f "src/app/bootstrap.php" ]; then
  echo "Warning: src/app/bootstrap.php not found - this may cause issues!"
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
echo "Testing health endpoints..."
echo "Testing _healthz endpoint (simplified):"
curl -s http://localhost:8080/public/_healthz
echo

echo "Testing health.php endpoint (if it exists):"
curl -s http://localhost:8080/public/health.php
echo

# Step 7: Get container logs
echo "Container logs:"
docker logs $CONTAINER_ID

# Step 8: Check directory structure inside the container
echo "Directory structure inside container:"
docker exec $CONTAINER_ID find /var/www/html -type d | sort

echo "Looking for bootstrap.php inside container:"
docker exec $CONTAINER_ID find /var/www/html -name "bootstrap.php" || echo "bootstrap.php not found in container!"

# Step 9: Stop the test container
echo "Stopping test container..."
docker stop $CONTAINER_ID

echo "==== Preparation complete. Ready for deployment ===="
echo "Run the following command to deploy to Cloud Run:"
echo "gcloud builds submit --config cloudbuild.yaml"
