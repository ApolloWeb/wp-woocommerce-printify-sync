/**
 * JavaScript file: update-readme.js for Printify Sync plugin
 *
 * Author: Rob Owen
 *
 * Last Update On: 2025-02-28 at 03:30:11
 */
const fs = require('fs');
const path = require('path');

// Define the base directory (repository root)
const baseDir = process.cwd();

// Example function to get files from a specific directory (for instance "src")
function getFileList(dir) {
  const fullDir = path.join(baseDir, dir);
  if (!fs.existsSync(fullDir)) {
    return [];
  }
  // Read all files in the directory (you can extend this to subdirectories if needed)
  return fs.readdirSync(fullDir).map(file => path.join(fullDir, file));
}

function updateReadme() {
  // Get list of files from the "src" directory (change as needed)
  const files = getFileList('src');
  
  // Remove the baseDir from the file paths and normalize slashes
  const fileList = files
    .map((file) => file.replace(baseDir, '').replace(/\/g, '/'))
    .join('
');

  const readmePath = path.join(baseDir, 'README.md');
  if (!fs.existsSync(readmePath)) {
    console.error('README.md not found at', readmePath);
    process.exit(1);
  }
  
  const readmeContent = fs.readFileSync(readmePath, 'utf8');
  
  // Update the file list in the README between custom markers. Adjust regex and markers as needed.
  const updatedContent = readmeContent.replace(
    /(<!-- FILE-LIST-START -->)([\s\S]*?)(<!-- FILE-LIST-END -->)/,
    `$1
${fileList}
$3`
  );
  
  fs.writeFileSync(readmePath, updatedContent, 'utf8');
  console.log('README updated with file list.');
}

updateReadme();
