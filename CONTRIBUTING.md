# Contributing to PteroCA

Thank you for your interest in contributing to **PteroCA**! We welcome contributions from everyone. Here's how you can help:

## How to Contribute

### Reporting Issues

1. **Search for duplicates**: Before creating a new issue, please check if a similar issue already exists in our [issue tracker](https://github.com/pteroca-com/panel/issues).
2. **Create a new issue**: If no existing issue matches your report, feel free to [create a new issue](https://github.com/pteroca-com/panel/issues/new). Make sure to include detailed information such as:
    - A clear description of the issue
    - Steps to reproduce the problem
    - Expected vs. actual behavior
    - Relevant logs or screenshots, if applicable
3. **Bug Reports**: Clearly explain the problem and how to reproduce it. Include information about your environment (e.g., OS, PHP version, Pterodactyl version).

### Suggesting Features

We love new ideas! If you have a feature request, follow these steps:
1. **Search for similar suggestions**: Check our existing feature requests in the [issue tracker](https://github.com/pteroca-com/panel/issues).
2. **Submit a new feature request**: If your idea is new, please create a [new issue](https://github.com/pteroca-com/panel/issues/new) and label it as a "Feature Request". Describe the feature and explain its use case.

### Submitting Pull Requests

1. **Fork the repository**: Create your own fork of the repository by clicking the "Fork" button in the top right of our [GitHub repository](https://github.com/pteroca-com/panel).
2. **Create a new branch**: Use a descriptive name for your branch. See [Branch Naming](#branch-naming) below.
3. **Make changes**: Add your code, following our coding standards:
    - Follow PSR-12 for PHP.
    - Write clear, concise code with comments where necessary.
    - Write tests for your code whenever possible.
4. **Commit your changes**: Write meaningful and descriptive commit messages. See [Commit Messages](#commit-messages) below.
5. **Push to your fork**: Push the changes from your local repository to your fork on GitHub.
6. **Submit a pull request**: From your forked repository, click the "New Pull Request" button. Make sure your PR targets the `main` branch.

## Branch Naming

Use the following naming conventions for your branches:

- `feature/<name>` — new functionality (e.g. `feature/add-payment-integration`)
- `bugfix/<name>` — bug fixes (e.g. `bugfix/fix-user-auth`)
- `hotfix/<name>` — urgent fixes for production issues
- `task-<id>-<name>` — tasks tracked in the issue tracker (e.g. `task-42-improve-logging`)

## Commit Messages

Write clear, descriptive commit messages in English. Focus on what the change does and why:

**Good examples:**
- `Add user balance notification on low funds`
- `Fix server suspension not triggered on expired subscription`
- `Refactor payment gateway to support multiple providers`

Avoid vague messages like `fix`, `update`, or `WIP`.

## Pull Request Process

1. **Target `main`** — all PRs should target the `main` branch.
2. **CI pipeline must pass** — the GitHub Actions CI pipeline runs automatically on every PR. A PR with a failing pipeline will not be reviewed. See [CI Pipeline](#ci-pipeline) for details.
3. **Code review** — a maintainer will review your pull request. Be patient; we will provide feedback as soon as possible.
4. **Address all comments** — all review comments must be addressed before the PR can be merged.
5. **Resolve all threads** — all discussion threads must be marked as resolved before merge.
6. **Merge** — once approved and all checks pass, a maintainer will merge the PR.

## CI Pipeline

Every pull request to `main` or `develop` automatically triggers the GitHub Actions CI pipeline (`symfony.yml`). The pipeline must pass before a PR will be reviewed or merged.

The pipeline checks:

- **PHPStan** (static analysis, level 1) — ensures no type errors or undefined references
- **Doctrine Migrations** — all migrations must execute without errors
- **Translation completeness** — no translation keys may be missing from any language file

### Running checks locally

Before pushing, you can run the checks inside the Docker development container:

```bash
# Static analysis
docker exec -it pteroca_web_dev vendor/bin/phpstan analyse

# Database migrations
docker exec -it pteroca_web_dev bin/console doctrine:migrations:migrate --no-interaction

# Translation check (replace messages.pl.yaml with any non-English file)
docker exec -it pteroca_web_dev bin/console app:show-missing-translations \
  src/Core/Resources/translations/messages.en.yaml \
  src/Core/Resources/translations/messages.pl.yaml
```

> **Note:** A PR with a failing CI pipeline will not be reviewed. Fix all issues before requesting a review.

## Setting Up Your Development Environment

PteroCA uses Docker for local development. Make sure you have [Docker](https://docs.docker.com/get-docker/) and [Docker Compose](https://docs.docker.com/compose/install/) installed.

1. **Clone the repository**:
    ```bash
    git clone https://github.com/pteroca-com/panel.git
    cd panel
    ```

2. **Configure environment**:
    ```bash
    cp .env.SAMPLE .env
    # Edit .env with your local settings
    ```

3. **Start the containers**:
    ```bash
    docker-compose up -d
    ```

4. **Run migrations**:
    ```bash
    docker exec -it pteroca_web_dev bin/console doctrine:migrations:migrate
    ```

5. **Start developing!**

> All Symfony and Composer commands must be executed inside the `pteroca_web_dev` container:
> ```bash
> docker exec -it pteroca_web_dev bin/console [symfony-command]
> docker exec -it pteroca_web_dev composer [composer-command]
> ```

For more details, see our [Documentation](https://docs.pteroca.com).

## Helping with Translations

We want PteroCA to be accessible to users all over the world. If you're interested in helping translate PteroCA into more languages, you can contribute directly via a pull request.

**Important rules for translations:**

- When **adding new translation keys or editing existing ones**, update all language files located in `src/Core/Resources/translations/`.
- The English file (`messages.en.yaml`) is the reference — make sure the key exists there first, then add the translated value to each other language file.
- The CI pipeline checks for missing translation keys — your PR will fail if any language file is incomplete.

## Community Guidelines

- **Be respectful**: We value a welcoming, respectful, and inclusive community. Disrespectful or inappropriate behavior will not be tolerated.
- **Collaboration**: Help fellow contributors by reviewing pull requests and participating in discussions.
- **Support**: For usage or configuration questions, please refer to our [Documentation](https://docs.pteroca.com) and the [Discord Support Server](https://discord.gg/Gz5phhuZym).

## License

By contributing to PteroCA, you agree that your contributions will be licensed under the [MIT License](https://github.com/pteroca-com/panel/blob/main/LICENSE).
