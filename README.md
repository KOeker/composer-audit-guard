# composer-audit-guard

[![Latest Stable Version](https://img.shields.io/packagist/v/koeker/composer-audit-guard.svg)](https://packagist.org/packages/koeker/composer-audit-guard)
[![Total Downloads](https://img.shields.io/packagist/dt/koeker/composer-audit-guard.svg?style=flat-square)](https://packagist.org/packages/koeker/composer-audit-guard)
![PHP Version](https://img.shields.io/packagist/php-v/koeker/composer-audit-guard)
![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)

A powerful CLI tool for Composer security audits with blacklist functionality and JUnit XML reports for CI/CD integration.

## ✨ Features

- 🔍 **Security Scanning**: Runs composer audit and groups vulnerabilities by severity level
- 🚫 **Blacklist Function**: Ignore known packages and display them separately
- 📊 **JUnit XML Reports**: Perfect for Jenkins, GitLab CI, and other CI/CD systems
- ⚙️ **Config File Support**: Configuration via `.auditguardrc.json` file
- 🎨 **Colored Output**: Clear, color-coded terminal output
- 🔄 **Flexible Options**: Scan with or without dev dependencies

## 📦 Installation

### Global

```bash
composer global require koeker/composer-audit-guard
```

Make sure your global composer bin directory is in your PATH.

### Local (per project)

```bash
composer require --dev koeker/composer-audit-guard
```

## 🚀 Usage

### Initialize Config File

Create a `.auditguardrc.json` config file in your project:

```bash
composer-audit-guard init
```

This creates a default config file that you can customize with your blacklist and settings.

### Basic Scan

Standard scan without dev dependencies:

```bash
composer-audit-guard
```

or with composer exec:

```bash
composer exec composer-audit-guard
```

### With Dev Dependencies

```bash
composer-audit-guard --dev
```

### With Blacklist

**Option 1: Using config file (recommended for multiple packages)**

```bash
# Create config file once
composer-audit-guard init

# Edit .auditguardrc.json and add your packages to the blacklist array
# Then just run:
composer-audit-guard
```

**Option 2: Command line (quick one-time use)**

```bash
composer-audit-guard --blacklist="symfony/http-kernel,guzzlehttp/guzzle,monolog/monolog"
```

### JUnit XML Report

```bash
# Standard output (./audit-results.xml)
composer-audit-guard --junit

# Custom output path
composer-audit-guard --junit --output="./test-results/security-audit.xml"
```

**Important:** The JUnit XML also includes **blacklist warnings as failures**!  
If a package is on the blacklist but has no security issues, this will be reported as a failure in Jenkins/CI.  
This helps keep your blacklist clean.

### Combined

```bash
composer-audit-guard --dev --blacklist="old-package,legacy-dep" --junit --output="./reports/audit.xml"
```

## 📋 Example Output

```
Running security audit... [████████████████████] 100%

=== Security Audit Results ===

Critical:
  - symfony/http-kernel (>=2.0.0 <5.4.20)
  - guzzlehttp/guzzle (>=6.0.0 <6.5.8)

High:
  - monolog/monolog (>=1.0.0 <1.27.1)

Moderate:
  - symfony/mime (>=4.3.0 <4.4.48)

Ignored (Blacklisted):
  - old-package (high)
  - legacy-dep (moderate)

Warning: Blacklisted package "another-package" not found in audit results

=== Summary ===
Total vulnerabilities found: 4
Ignored (blacklisted): 2

✗ 4 vulnerabilities require attention!
```

## ⚙️ Configuration

### Config File (.auditguardrc.json)

You can create a config file to persist your audit settings. **The file is automatically loaded** if it exists in your project directory.

**Option 1: Using the init command (recommended)**

```bash
composer-audit-guard init
```

This creates a `.auditguardrc.json` file with default settings that you can customize.

**Option 2: Manual creation**

Create a `.auditguardrc.json` file in your project root:

```json
{
  "blacklist": [
    "package-name-1",
    "package-name-2",
    "old-legacy-package"
  ],
  "includeDev": false
}
```

**How it works:**
- ✅ The config file is **automatically detected and loaded** - no extra command needed
- ✅ Run `composer-audit-guard` and it will use your blacklist from the config file
- ⚠️ **CLI parameters override config file settings** (e.g., `--blacklist` on command line takes priority)

### CLI Commands & Options

**Commands:**

| Command | Description | Example |
|---------|-------------|---------|
| `composer-audit-guard` | Run security audit (default) | `composer-audit-guard --dev` |
| `composer-audit-guard init` | Create .auditguardrc.json config file | `composer-audit-guard init` |

**Options:**

| Option | Description | Example |
|--------|-------------|---------|
| `--dev` | Include dev dependencies in scan | `composer-audit-guard --dev` |
| `--blacklist <packages>` | Comma-separated list of packages to ignore | `composer-audit-guard --blacklist="pkg1,pkg2"` |
| `--junit` | Generate JUnit XML report | `composer-audit-guard --junit` |
| `--output <path>` | Path for JUnit XML output | `composer-audit-guard --output="./reports/audit.xml"` |
| `--help` | Display help | `composer-audit-guard --help` |
| `--version` | Display version | `composer-audit-guard --version` |

## 🔄 CI/CD Integration

### Jenkins

```groovy
pipeline {
    agent any
    stages {
        stage('Security Audit') {
            steps {
                sh 'composer exec composer-audit-guard -- --junit --output="./test-results/audit.xml"'
            }
            post {
                always {
                    junit 'test-results/audit.xml'
                }
            }
        }
    }
}
```

### GitLab CI

```yaml
security_audit:
  script:
    - composer exec composer-audit-guard -- --junit --output="audit-results.xml"
  artifacts:
    when: always
    reports:
      junit: audit-results.xml
```

### GitHub Actions

```yaml
name: Security Audit

on: [push, pull_request]

jobs:
  audit:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      - uses: shivammathur/setup-php@v2
        with:
          php-version: '8.2'
      - run: composer install
      - run: composer exec composer-audit-guard -- --junit --output="audit-results.xml"
      - uses: actions/upload-artifact@v3
        if: always()
        with:
          name: audit-results
          path: audit-results.xml
```

## 📊 Exit Codes

| Code | Meaning |
|------|---------|
| `0` | No vulnerabilities found (or all ignored) |
| `1` | Vulnerabilities found |
| `2` | Execution error (e.g., no composer.json) |

## 📄 Requirements

- PHP >= 8.0
- Composer >= 2.0

## 🔗 Related Projects

- [npm-audit-guard](https://github.com/KOeker/npm-audit-guard) - The npm/Node.js version of this tool

## 📝 License

MIT License - see [LICENSE](LICENSE) file for details.