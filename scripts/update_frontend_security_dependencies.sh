#!/usr/bin/env bash
set -euo pipefail

if [[ ! -f package.json || ! -f package-lock.json ]]; then
    echo "Run this script from the Laravel project root." >&2
    exit 1
fi

# Vite 7 and laravel-vite-plugin 2.x require Node 20.19+ or Node 22.12+.
node <<'NODE'
const [major, minor] = process.versions.node.split('.').map(Number);
const supported = major > 22
    || (major === 22 && minor >= 12)
    || (major === 20 && minor >= 19);

if (!supported) {
    console.error(`Unsupported Node.js ${process.versions.node}. Use Node.js 20.19+ or 22.12+ before continuing.`);
    process.exit(1);
}
NODE

# Keep the project on its existing compatible major versions.
# concurrently 9.2.2 was never published; 9.2.4 is the valid patched 9.x release.
npm install --save-dev --save-exact \
    axios@1.18.1 \
    concurrently@9.2.4 \
    vite@7.3.6

# Refresh vulnerable transitive packages within the dependency ranges already
# allowed by package.json/package-lock.json. This does not request a forced
# major-version upgrade.
npm audit fix

# Fail if npm still reports a moderate-or-higher vulnerability.
npm audit --audit-level=moderate

# Verify that the updated dependency tree can compile the application assets.
npm run build

echo "Frontend security dependency update completed successfully."
echo "Review and commit package.json and package-lock.json."
