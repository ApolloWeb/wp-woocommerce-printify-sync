const fs = require("fs");
const path = require("path");
const { execSync } = require("child_process");

// Define base directory
const baseDir = process.cwd();

// Function to get file list recursively
function getFileList(dir, parentDir = "") {
  const fullDir = path.join(baseDir, dir);
  if (!fs.existsSync(fullDir)) return [];

  const ignoreDirs = [".git", ".github", "node_modules", "vendor"];
  const files = fs
    .readdirSync(fullDir)
    .filter((file) => !ignoreDirs.includes(file))
    .map((file) => {
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

// Function to generate file descriptions using GitHub Copilot CLI
function getFileDescription(file) {
  console.log(`🔍 Generating description for: ${file}`);

  try {
    // Read first few lines of the file
    const filePath = path.join(baseDir, file);
    let content = "";
    if (fs.existsSync(filePath) && fs.statSync(filePath).isFile()) {
      content = fs.readFileSync(filePath, "utf8").split("\n").slice(0, 10).join(" ");
    }

    // Construct a Copilot prompt
    const prompt = `Describe the purpose of this file: ${file} based on the following content:\n${content}`;

    // Call Copilot CLI
    const description = execSync(`gh copilot suggest -t shell "${prompt}"`, {
      encoding: "utf-8",
      stdio: ["pipe", "pipe", "ignore"],
    }).trim();

    return description || `- **${file}**: No description available.`;
  } catch (error) {
    console.warn(`⚠️ Copilot failed for ${file}, using fallback.`);
    return `- **${file}**: No description available.`;
  }
}

// Update README file
function updateReadme() {
  const files = getFileList("");

  // Generate file structure and descriptions
  const fileStructure = files.map((file) => `      ${file}`).join("\n");
  const fileDescriptions = files.map((file) => getFileDescription(file)).join("\n");

  // Read README template
  const readmeTemplatePath = path.join(baseDir, "README_TEMPLATE.txt");
  if (!fs.existsSync(readmeTemplatePath)) {
    console.error("❌ README_TEMPLATE.txt not found.");
    process.exit(1);
  }

  const readmeTemplateContent = fs.readFileSync(readmeTemplatePath, "utf8");

  // Replace placeholders in README
  const updatedContent = readmeTemplateContent
    .replace(
      /(<!-- FILE-STRUCTURE-START -->)([\s\S]*?)(<!-- FILE-STRUCTURE-END -->)/,
      `$1\n${fileStructure}\n$3`
    )
    .replace(
      /(<!-- FILE-DESCRIPTIONS-START -->)([\s\S]*?)(<!-- FILE-DESCRIPTIONS-END -->)/,
      `$1\n${fileDescriptions}\n$3`
    );

  // Write to README.md
  const readmePath = path.join(baseDir, "README.md");
  fs.writeFileSync(readmePath, updatedContent, "utf8");

  console.log("📄 README updated with file structure and Copilot-generated descriptions.");
}

// Run the script
updateReadme();
