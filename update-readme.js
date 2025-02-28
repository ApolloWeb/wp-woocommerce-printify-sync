const fs = require("fs");
const path = require("path");
const axios = require("axios");
const { execSync } = require("child_process");

// Define base directory
const baseDir = process.cwd();

// Load excluded files from `.readme_exclude`
const excludeFilePath = path.join(baseDir, ".readme_exclude");
let excludeFiles = new Set([".gitignore", ".header_exclude", ".vscode/settings.json", ".wp-env.json", "update-readme.js"]);

if (fs.existsSync(excludeFilePath)) {
  const fileContent = fs.readFileSync(excludeFilePath, "utf8")
    .split("\n")
    .map(line => line.trim())
    .filter(line => line.length > 0 && !line.startsWith("#"));
  excludeFiles = new Set([...excludeFiles, ...fileContent]);
}

// Function to get only staged files using `git diff --cached`
function getStagedFiles() {
  try {
    const output = execSync("git diff --cached --name-only", { encoding: "utf-8" });
    const files = output.split("\n").filter(file => file.trim() !== "" && !excludeFiles.has(file));
    return files;
  } catch (error) {
    console.error("❌ Error getting staged files. Skipping README update.");
    return [];
  }
}

// Function to extract existing file descriptions from README
function getExistingDescriptions(readmePath) {
  if (!fs.existsSync(readmePath)) return {};

  const readmeContent = fs.readFileSync(readmePath, "utf8");
  const descriptions = {};

  const match = readmeContent.match(/<!-- FILE-DESCRIPTIONS-START -->([\s\S]*?)<!-- FILE-DESCRIPTIONS-END -->/);
  if (match) {
    match[1].split("\n").forEach(line => {
      const matchFile = line.match(/- \*\*(.*?)\*\*: (.*)/);
      if (matchFile) {
        descriptions[matchFile[1]] = matchFile[2];
      }
    });
  }

  return descriptions;
}

// Function to get file list while preserving hierarchy
function getFileList(dir = "", parentDir = "") {
  const fullDir = path.join(baseDir, dir);
  if (!fs.existsSync(fullDir)) return [];

  const ignoreDirs = [".git", ".github", "node_modules", "vendor"];
  const files = fs
    .readdirSync(fullDir)
    .filter((file) => !ignoreDirs.includes(file) && !excludeFiles.has(path.join(parentDir, file)))
    .map((file) => {
      const fullPath = path.join(fullDir, file);
      const relativePath = path.join(parentDir, file);
      if (fs.statSync(fullPath).isDirectory()) {
        return { type: "folder", name: relativePath, children: getFileList(path.join(dir, file), relativePath) };
      } else {
        return { type: "file", name: relativePath };
      }
    });

  return files;
}

// Function to batch API requests
async function getFileDescriptionsBatch(files) {
  if (files.length === 0) return {};

  console.log(`🔍 Generating descriptions for ${files.length} files in batches...`);

  let descriptions = {};
  let batchSize = 10;  // Adjust batch size to balance API efficiency
  for (let i = 0; i < files.length; i += batchSize) {
    const batch = files.slice(i, i + batchSize);
    
    let batchContent = batch.map(file => `- **${file}**:`).join("\n");

    console.log(`📦 Sending batch ${i / batchSize + 1}: ${batch.length} files`);

    try {
      if (!process.env.OPENAI_API_KEY) {
        console.warn("⚠️ OpenAI API key is missing. Using fallback.");
        batch.forEach(file => descriptions[file] = "(No description available)");
        continue;
      }

      const response = await axios.post(
        "https://api.openai.com/v1/chat/completions",
        {
          model: "gpt-4o",
          messages: [
            { role: "system", content: `You are an AI that generates concise 1-line descriptions for files in a project.` },
            { role: "user", content: `Provide descriptions for these files:\n\n${batchContent}\n\nFormat each as "- **filename**: description"` }
          ],
          temperature: 0.5,
          max_tokens: 300
        },
        {
          headers: {
            "Authorization": `Bearer ${process.env.OPENAI_API_KEY}`,
            "Content-Type": "application/json"
          }
        }
      );

      const responseText = response.data.choices[0].message.content.trim();
      console.log("📥 AI Response:", responseText);

      responseText.split("\n").forEach(line => {
        const match = line.match(/- \*\*(.*?)\*\*: (.*)/);
        if (match) {
          descriptions[match[1]] = match[2];
        }
      });
    } catch (error) {
      console.warn(`⚠️ Failed to generate descriptions for batch ${i / batchSize + 1}. Retrying...`);
      console.error(error.message);
      batch.forEach(file => descriptions[file] = "(No description available)");
      await new Promise(res => setTimeout(res, 2000));  // Wait before retrying
    }
  }

  return descriptions;
}

// Update README file while preserving structure
async function updateReadme() {
  const readmePath = path.join(baseDir, "README.md");
  const readmeTemplatePath = path.join(baseDir, "README_TEMPLATE.txt");

  if (!fs.existsSync(readmeTemplatePath)) {
    console.error("❌ README_TEMPLATE.txt not found.");
    process.exit(1);
  }

  // Get existing descriptions
  let existingDescriptions = getExistingDescriptions(readmePath);
  const stagedFiles = getStagedFiles();

  if (stagedFiles.length === 0) {
    console.log("✅ No staged files found. Skipping README update.");
    return;
  }

  // Generate descriptions for staged files in batches
  const newDescriptions = await getFileDescriptionsBatch(stagedFiles);
  existingDescriptions = { ...existingDescriptions, ...newDescriptions };

  // Generate structured README
  const structuredFiles = getFileList();
  const structuredReadme = structuredFiles
    .map(file => `- **${file.name}**: ${existingDescriptions[file.name] || "(No description available)"}`)
    .join("\n");

  // Read README template
  const readmeTemplateContent = fs.readFileSync(readmeTemplatePath, "utf8");

  // Replace placeholders in README
  const updatedContent = readmeTemplateContent
    .replace(/(<!-- FILE-DESCRIPTIONS-START -->)([\s\S]*?)(<!-- FILE-DESCRIPTIONS-END -->)/, `$1\n${structuredReadme}\n$3`);

  // Write to README.md
  fs.writeFileSync(readmePath, updatedContent, "utf8");

  console.log("📄 README updated successfully!");
}

// Run the script
updateReadme();
