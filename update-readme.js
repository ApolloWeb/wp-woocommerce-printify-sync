const fs = require("fs");
const path = require("path");
const axios = require("axios");
const { execSync } = require("child_process");

// Define base directory
const baseDir = process.cwd();

// Function to get only **staged** files using `git diff --cached`
function getStagedFiles() {
  try {
    const output = execSync("git diff --cached --name-only", { encoding: "utf-8" });
    const files = output.split("\n").filter(file => file.trim() !== "");
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

// Function to get the entire file structure while preserving hierarchy
function getFileList(dir = "", parentDir = "") {
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
        return { type: "folder", name: relativePath, children: getFileList(path.join(dir, file), relativePath) };
      } else {
        return { type: "file", name: relativePath };
      }
    });

  return files;
}

// Function to generate concise file descriptions using OpenAI
async function getFileDescription(file) {
  console.log(`🔍 Generating concise description for: ${file}`);

  try {
    if (!process.env.OPENAI_API_KEY) {
      console.warn("⚠️ OpenAI API key is missing. Using fallback.");
      return `(No description available)`;
    }

    // Read first 2 lines of the file (reduce token usage)
    const filePath = path.join(baseDir, file);
    let content = "";
    if (fs.existsSync(filePath) && fs.statSync(filePath).isFile()) {
      content = fs.readFileSync(filePath, "utf8").split("\n").slice(0, 2).join(" ");
    }

    // Call OpenAI API with a request for a **concise** description
    const response = await axios.post(
      "https://api.openai.com/v1/chat/completions",
      {
        model: "gpt-4",
        messages: [
          { role: "system", content: `Provide a concise 1-line description for this file:\n${file}\nContent:\n${content}` }
        ],
        temperature: 0.5,
        max_tokens: 30
      },
      {
        headers: {
          "Authorization": `Bearer ${process.env.OPENAI_API_KEY}`,
          "Content-Type": "application/json"
        }
      }
    );

    return response.data.choices[0].message.content.trim();
  } catch (error) {
    console.warn(`⚠️ Failed to generate description for ${file}, using fallback.`);
    return `(No description available)`;
  }
}

// Generate structured README content with indentation
function generateStructuredReadme(files, descriptions, indent = 0) {
  let result = "";
  const indentation = " ".repeat(indent * 2);

  for (const file of files) {
    if (file.type === "folder") {
      result += `${indentation}- **${file.name}/**\n`;
      result += generateStructuredReadme(file.children, descriptions, indent + 1);
    } else {
      const desc = descriptions[file.name] || "(No description available)";
      result += `${indentation}- **${file.name}**: ${desc}\n`;
    }
  }

  return result;
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

  // Generate descriptions only for staged files
  for (const file of stagedFiles) {
    existingDescriptions[file] = await getFileDescription(file);
  }

  // Generate file structure
  const structuredFiles = getFileList();
  const structuredReadme = generateStructuredReadme(structuredFiles, existingDescriptions);

  // Read README template
  const readmeTemplateContent = fs.readFileSync(readmeTemplatePath, "utf8");

  // Replace placeholders in README
  const updatedContent = readmeTemplateContent
    .replace(
      /(<!-- FILE-STRUCTURE-START -->)([\s\S]*?)(<!-- FILE-STRUCTURE-END -->)/,
      `$1\n${structuredReadme}\n$3`
    )
    .replace(
      /(<!-- FILE-DESCRIPTIONS-START -->)([\s\S]*?)(<!-- FILE-DESCRIPTIONS-END -->)/,
      `$1\n${structuredReadme}\n$3`
    );

  // Write to README.md
  fs.writeFileSync(readmePath, updatedContent, "utf8");

  console.log("📄 README updated while preserving file structure and tracking only staged changes.");
}

// Run the script
updateReadme();
