const fs = require('fs');
const path = require('path');
const { execSync } = require('child_process');

// Function to read the .readme_exclude file and return the list of excluded files
function getExcludedFiles() {
  const excludeFilePath = path.join(process.cwd(), '.readme_exclude');
  
  // If .readme_exclude file doesn't exist, return an empty array
  if (!fs.existsSync(excludeFilePath)) {
    return [];
  }

  const excludeContent = fs.readFileSync(excludeFilePath, 'utf8');
  const excludedFiles = excludeContent
    .split('\n')
    .map(line => line.trim())
    .filter(line => line.length > 0 && !line.startsWith('#'));  // Remove comments and empty lines

  return excludedFiles;
}

// Function to get the list of changed files from git
function getChangedFiles() {
  try {
    // Get the diff between the current HEAD and the previous commit
    let output = execSync('git diff --name-only HEAD^ HEAD', { encoding: 'utf-8' });
    
    // If HEAD^ fails (e.g., on a fresh repository), fall back to using HEAD~1
    if (!output) {
      output = execSync('git diff --name-only HEAD~1 HEAD', { encoding: 'utf-8' });
    }

    const files = output.split('\n').filter(file => file.trim() !== '');
    return files;
  } catch (error) {
    console.error('❌ Error getting changed files:', error);
    return [];
  }
}

// Function to update the README file with changed files
function updateReadme(changedFiles, excludedFiles) {
  const readmePath = path.join(process.cwd(), 'README.md');

  if (!fs.existsSync(readmePath)) {
    console.error('❌ README.md not found.');
    return;
  }

  let readmeContent = fs.readFileSync(readmePath, 'utf8');

  // Filter out files that are in the .readme_exclude list
  const filesToUpdate = changedFiles.filter(file => !excludedFiles.includes(file));

  // If no files to update, skip the process
  if (filesToUpdate.length === 0) {
    console.log('⚠️ No files to update for README after applying exclusions.');
    return;
  }

  // Create a section for changed files in the README
  let changedFilesSection = '## Changed Files:\n';
  filesToUpdate.forEach(file => {
    let description = `(No description available for ${file})`;

    if (file.endsWith('.js')) {
      description = 'Update JavaScript file.';
    } else if (file.endsWith('.php')) {
      description = 'Update PHP file.';
    } else if (file.endsWith('.css')) {
      description = 'Update CSS styles.';
    }

    // Add file and description to the section
    changedFilesSection += `- **${file}**: ${description}\n`;
  });

  // Append the changed files section to the README (or update if it exists)
  const sectionStart = '## Changed Files:';
  const sectionEnd = '## Other Sections';  // Define the start of the next section if any

  const sectionRegEx = new RegExp(`(${sectionStart}[\\s\\S]*?${sectionEnd})`, 'g');
  const updatedReadmeContent = readmeContent.replace(sectionRegEx, `${sectionStart}\n${changedFilesSection}\n${sectionEnd}`);

  // Write the updated content back to the README
  fs.writeFileSync(readmePath, updatedReadmeContent, 'utf8');
  console.log('✅ README.md updated successfully!');
}

// Main function to execute the process
function main() {
  const excludedFiles = getExcludedFiles();  // Get the list of files to exclude
  const changedFiles = getChangedFiles();  // Get the list of changed files

  if (changedFiles.length > 0) {
    updateReadme(changedFiles, excludedFiles);  // Update README excluding the files listed in .readme_exclude
  } else {
    console.log('⚠️ No files to update for README.');
  }
}

main();
