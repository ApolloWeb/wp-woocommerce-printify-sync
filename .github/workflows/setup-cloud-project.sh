cat > ~/docker/templates/setup-cloud-project.sh << 'EOF'
#!/bin/bash

if [ -z "$1" ]; then
    echo "Usage: $0 project-name [environment]"
    echo "Environment can be: dev, staging, prod (defaults to dev)"
    exit 1
fi

PROJECT_NAME=$1
ENV=${2:-dev}
PROJECT_DIR=~/docker/projects/${ENV}/${PROJECT_NAME}

# Create project structure
mkdir -p "${PROJECT_DIR}"/.github/workflows

# Copy templates
cp ~/docker/templates/Dockerfile.cloud "${PROJECT_DIR}/Dockerfile"
cp ~/docker/templates/docker-build.yml "${PROJECT_DIR}/.github/workflows/docker-build.yml"

# Create .dockerignore
cat > "${PROJECT_DIR}/.dockerignore" << 'INNEREOF'
.git
.gitignore
node_modules
**/node_modules
.env
*.log
.vscode
.idea
**/.DS_Store
.github
README.md
INNEREOF

# Create README
cat > "${PROJECT_DIR}/README.md" << INNEREOF
# ${PROJECT_NAME}

Docker project using Docker Build Cloud

## Development

### Prerequisites
- Docker Desktop with Build Cloud enabled
- GitHub account with Docker Hub integration

### Building Locally
\`\`\`bash
# Build using Docker Build Cloud
docker build -t ${PROJECT_NAME}:dev .

# Run locally
docker run -d -p 3000:3000 ${PROJECT_NAME}:dev
\`\`\`

### GitHub Actions
This project uses GitHub Actions for CI/CD with Docker Build Cloud.
The workflow will:
1. Build the image using Docker Build Cloud
2. Push to Docker Hub
3. Use GitHub's cache for faster builds

### Configuration
- Update the Docker Hub repository in \`.github/workflows/docker-build.yml\`
- Set DOCKERHUB_USERNAME and DOCKERHUB_TOKEN in GitHub Secrets
INNEREOF

echo "Project ${PROJECT_NAME} created in ${PROJECT_DIR}"
ls -la "${PROJECT_DIR}"
EOF

chmod +x ~/docker/templates/setup-cloud-project.sh