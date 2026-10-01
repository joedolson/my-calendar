const { execFileSync } = require("child_process");
const path = require("path");

const ROOT_DIR = path.resolve(__dirname, "..", "..", "..");
const NPX = process.platform === "win32" ? "npx.cmd" : "npx";

/**
 * Run a wp-cli command inside the wp-env container.
 *
 * @param {string[]} args wp-cli arguments, e.g. [ 'post', 'list' ].
 */
const wpCli = (args) => {
  return execFileSync(NPX, ["wp-env", "run", "cli", "wp", ...args], {
    cwd: ROOT_DIR,
    encoding: "utf-8",
  });
};

/**
 * Run a PHP snippet inside the wp-env container via `wp eval`.
 *
 * @param {string} php PHP statements (no opening `<?php` tag).
 * @return {string} Trimmed command output.
 */
const wpEval = (php) => {
  return wpCli(["eval", php]).trim();
};

module.exports = { wpCli, wpEval };
