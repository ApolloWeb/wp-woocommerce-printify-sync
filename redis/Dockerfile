# Use official Redis image
FROM redis:latest

# Set environment variables
ENV REDIS_PORT=6379 \
    REDIS_MAXMEMORY=256mb \
    REDIS_POLICY=allkeys-lru

# Expose Redis port
EXPOSE $REDIS_PORT

# Start Redis with authentication and security settings
CMD ["sh", "-c", "redis-server --port $REDIS_PORT --maxmemory $REDIS_MAXMEMORY --maxmemory-policy $REDIS_POLICY --bind 0.0.0.0 --protected-mode yes"]
