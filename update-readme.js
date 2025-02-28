const fs = require('fs');
const path = require('path');

// Define the base directory (repository root)
const baseDir = process.cwd();

// Function to recursively get files from a specific directory
function getFileList(dir, parentDir = '') {
  const fullDir = path.join(baseDir, dir);
  if (!fs.existsSync(fullDir)) {
    return [];
  }
  const files = fs.readdirSync(fullDir).map(file => {
    const fullPath = path.join(fullDir, file);
    const relativePath = path.join(parentDir, file);
    if (fs.statSync(fullPath).isDirectory()) {
      return getFileList(path.join(dir, file), relativePath);
    } else {
      return relativePath;
    }
  });
  return files.flat();
}

function updateReadme() {
  // Get list of files from the root directory
  const files = getFileList('');

  // Generate file structure and descriptions
  const fileStructure = files.map(file => `      ${file}`).join('\n');
  const fileDescriptions = files.map(file => {
    const fileName = path.basename(file);
    return `- **${fileName}**: Description of ${fileName}`;
  }).join('\n');

  const readmePath = path.join(baseDir, 'README.md');
  if (!fs.existsSync(readmePath)) {
    console.error('README.md not found at', readmePath);
    process.exit(1);
  }

  const readmeContent = fs.readFileSync(readmePath, 'utf8');

  // Update the file structure and descriptions in the README between custom markers
  const updatedContent = readmeContent
    .replace(
      /(<!-- FILE-STRUCTURE-START -->)([\s\S]*?)(<!-- FILE-STRUCTURE-END -->)/,
      `$1\n${fileStructure}\n$3`
    )
    .replace(
      /(<!-- FILE-DESCRIPTIONS-START -->)([\s\S]*?)(<!-- FILE-DESCRIPTIONS-END -->)/,
      `$1\n${fileDescriptions}\n$3`
    );

  fs.writeFileSync(readmePath, updatedContent, 'utf8');
  console.log('README updated with file structure and descriptions.');
}

updateReadme();