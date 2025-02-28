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

  // Define directories to ignore
  const ignoreDirs = ['.git', 'node_modules', 'vendor'];

  const files = fs.readdirSync(fullDir).filter(file => !ignoreDirs.includes(file)).map(file => {
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

  const readmeTemplatePath = path.join(baseDir, 'README_TEMPLATE.txt'); // Path to the template
  if (!fs.existsSync(readmeTemplatePath)) {
    console.error('README_TEMPLATE.txt not found at', readmeTemplatePath);
    process.exit(1);
  }

  const readmeTemplateContent = fs.readFileSync(readmeTemplatePath, 'utf8'); // Read the template

  // Update the file structure and descriptions in the template content between custom markers
  const updatedContent = readmeTemplateContent
    .replace(
      /(<!-- FILE-STRUCTURE-START -->)([\s\S]*?)(<!-- FILE-STRUCTURE-END -->)/,
      `$1\n${fileStructure}\n$3`
    )
    .replace(
      /(<!-- FILE-DESCRIPTIONS-START -->)([\s\S]*?)(<!-- FILE-DESCRIPTIONS-END -->)/,
      `$1\n${fileDescriptions}\n$3`
    );

    const readmePath = path.join(baseDir, 'README.md');

  fs.writeFileSync(readmePath, updatedContent, 'utf8');
  console.log('README updated with file structure and descriptions.');
}

updateReadme();