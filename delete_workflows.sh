#!/bin/bash

# Ensure the GitHub CLI is authenticated
if ! gh auth status &>/dev/null; then
    echo "❌ GitHub CLI is not authenticated. Please run 'gh auth login' first."
    exit 1
fi

# Get the repository name (you can modify this or set it manually)
REPO=$(gh repo view --json nameWithOwner -q '.nameWithOwner')

echo "📌 Target repository: $REPO"

# Fetch all workflow runs
RUNS=$(gh api repos/$REPO/actions/runs --paginate --jq '.workflow_runs[].id')

# Check if there are any runs to delete
if [[ -z "$RUNS" ]]; then
    echo "✅ No workflow runs to delete."
    exit 0
fi

# Loop through and delete each run
for RUN_ID in $RUNS; do
    echo "🗑️ Deleting workflow run ID: $RUN_ID"
    gh api -X DELETE repos/$REPO/actions/runs/$RUN_ID
done

echo "🎉 All workflow runs have been deleted!"
exit 0
