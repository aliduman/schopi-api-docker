#!/bin/bash

# This script tests the Docker image locally before deployment

echo "==== Testing Docker Image for Cloud Run ===="

# Step 1: Build the Docker image
echo "Building Docker image..."
docker build -t schopi-api-docker:test .

# Check if the build was successful
if [ $? -ne 0 ]; then
  echo "Error: Docker build failed!"
  exit 1
fi

# Step 2: Run the container
echo "Running container..."
CONTAINER_ID=$(docker run -d -p 8080:8080 -e PORT=8080 schopi-api-docker:test)

# Check if the container started
if [ -z "$CONTAINER_ID" ]; then
  echo "Error: Failed to start container!"
  exit 1
fi

echo "Container started with ID: $CONTAINER_ID"

# Step 3: Wait for the server to start
echo "Waiting for server to start..."
sleep 5

# Step 4: Test the health endpoint
echo "Testing health endpoint..."
HEALTH_RESPONSE=$(curl -s http://localhost:8080/_healthz)
echo "Health response: $HEALTH_RESPONSE"

# Step 5: Show container logs
echo "Container logs:"
docker logs $CONTAINER_ID

# Step 6: Clean up
echo "Stopping container..."
docker stop $CONTAINER_ID

echo "==== Test completed ===="
echo "If the health endpoint returned a valid JSON response, the container is ready for deployment."
