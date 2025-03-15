# Use the official Redis image
FROM redis:latest

# Expose Redis port
EXPOSE 6379

# Start Redis with Render's environment variables
CMD ["sh", "-c", "redis-server --requirepass \"$REDIS_PASSWORD\" --maxmemory \"$REDIS_MAXMEMORY\" --maxmemory-policy \"$REDIS_POLICY\""]
