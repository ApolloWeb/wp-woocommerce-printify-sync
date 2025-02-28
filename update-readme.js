const fs = require('fs');
const path = require('path');

// Define the base directory (repository root)
const baseDir = process.cwd();

// Function to get files from a specific directory (for example, "src")
function getFileList(dir) {
  const fullDir = path.join(baseDir, dir);
  if (!fs.existsSync(fullDir)) {
    return [];
  }
  // Read all files in the directory (this example does not traverse subdirectories)
  return fs.readdirSync(fullDir).map(file => path.join(fullDir, file));
}

function updateReadme() {
  // Get list of files from the "src" directory (adjust as needed)
  const files = getFileList('src');

  // Remove the baseDir from the file paths and replace backslashes with forward slashes
  const fileList = files
    .map(file => file.replace(baseDir, '').replace(/\\/g, '/'))
    .join('\n');

  const readmePath = path.join(baseDir, 'README.md');
  if (!fs.existsSync(readmePath)) {
    console.error('README.md not found at', readmePath);
    process.exit(1);
  }

  const readmeContent = fs.readFileSync(readmePath, 'utf8');

  // Update the file list in the README between custom markers.
  // Make sure your README.md contains the markers: <!-- FILE-LIST-START --> and <!-- FILE-LIST-END -->
  const updatedContent = readmeContent.replace(
    /(<!-- FILE-LIST-START -->)([\s\S]*?)(<!-- FILE-LIST-END -->)/,
    `$1\n${fileList}\n$3`
  );

  fs.writeFileSync(readmePath, updatedContent, 'utf8');
  console.log('README updated with file list.');
}

updateReadme();