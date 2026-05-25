# Contributing to Green

Thank you for your interest in contributing to **Green**.

This repository is for the **Green starter project**.  
Changes related to the framework core should go to **green-core**.

## Before contributing

- Create your branch from `dev`
- Keep your changes small and focused
- Follow the existing code style
- Update documentation if needed
- Test your changes before opening a PR

## Setup

```bash
git clone https://github.com/YasserElgammal/green.git
cd green
composer install
cp .env.example .env
php green serve
```

## Branch example

```bash
git checkout dev
git pull origin dev
git checkout -b feat/your-change-name
```

## Commit examples

```bash
feat: add new starter config
fix: correct route example
docs: update setup guide
refactor: clean bootstrap code
test: add starter test
```

## Pull request

Your PR should explain:

- What changed
- Why it changed
- How you tested it

## Checklist

- [ ] Change belongs in `green`
- [ ] Code is simple and readable
- [ ] Tests pass
- [ ] Docs updated if needed

Thanks for helping improve Green.