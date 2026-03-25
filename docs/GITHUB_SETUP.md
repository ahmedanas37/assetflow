# GitHub Setup (Safe Baseline)

Use this checklist when publishing a new AssetFlow instance repository.

## 1) Authenticate Git on the server
Use HTTPS with a fine-grained Personal Access Token (PAT).

```bash
git config --global credential.helper store
git push -u origin main
```

When prompted:
- Username: your GitHub username
- Password: paste the PAT (not your GitHub account password)

Recommended token scope for this repo:
- `Contents: Read and write`
- `Metadata: Read-only` (auto-included)

## 2) Confirm first push

```bash
git log --oneline -n 5
git remote -v
```

Verify `main` exists on GitHub and commit history matches local.

## 3) Turn on repository protections
In GitHub repository settings:
- Enable branch protection for `main`:
  - Require pull request before merge
  - Require status checks to pass
  - Restrict direct pushes (optional but recommended)
- Enable Dependabot alerts
- Enable secret scanning (if your GitHub plan supports it)
- Keep the `Secret Scan` workflow enabled (`.github/workflows/secret-scan.yml`)

## 4) Add repository secrets for CI/CD (if needed later)
Do not commit secrets into files. Add them in:
`Settings > Secrets and variables > Actions`

Typical examples:
- `APP_KEY`
- Deployment SSH keys
- Private package tokens

## 5) Keep runtime data out of Git
Current `.gitignore` already excludes:
- `.env` and production env files
- `vendor/`, `node_modules/`
- `storage/framework/cache/data/*`
- `storage/framework/sessions/*`
- `storage/framework/testing/*`
- `storage/framework/views/*`
- `storage/logs/*`
- `storage/app/private/*`
- `storage/app/public/*` (except placeholder `.gitignore`)
- `bootstrap/cache/*.php`

Before each push, run:

```bash
git status
git ls-files | rg -n "(\\.env$|\\.pem$|\\.p12$|\\.pfx$|id_rsa|id_ed25519)" || true
```

If runtime files appear, stop and fix `.gitignore` before committing.

## 6) Recommended release flow
- Work in feature branches
- Open pull request to `main`
- Wait for CI (`.github/workflows/ci.yml`) to pass
- Merge and tag release (`v1.0.0`, `v1.0.1`, etc.)
