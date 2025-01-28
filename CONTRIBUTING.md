# Contributing to PteroCA

Thank you for your interest in contributing to **PteroCA**! We welcome contributions from everyone. Here’s how you can help:

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
2. **Create a new branch**: Use a descriptive name for your branch. For example: `feature/add-payment-integration` or `bugfix/fix-user-auth`.
3. **Make changes**: Add your code, following our coding standards:
    - Follow PSR-12 for PHP.
    - Write clear, concise code with comments where necessary.
    - Write tests for your code whenever possible.
4. **Commit your changes**: Write meaningful and descriptive commit messages.
5. **Push to your fork**: Push the changes from your local repository to your fork on GitHub.
6. **Submit a pull request**: From your forked repository, click the “New Pull Request” button. Ensure that your pull request is well-documented and references any issues it resolves.

### Code Review and Feedback

- **Be patient**: Our maintainers will review your pull request as soon as possible. We may ask you to make additional changes.
- **Tests**: Ensure your pull request passes all tests before submission. Use PHPUnit for testing the PHP code.
- **Constructive Feedback**: If your pull request is rejected, don’t be discouraged. We will provide constructive feedback to help you improve.

## Community Guidelines

- **Be respectful**: We value a welcoming, respectful, and inclusive community. Disrespectful or inappropriate behavior will not be tolerated.
- **Collaboration**: Help fellow contributors by reviewing pull requests and participating in discussions.
- **Support**: For usage or configuration questions, please refer to our [Documentation](https://pteroca.gitbook.io) and the [Discord Support Server](https://discord.gg/Gz5phhuZym).

## Setting Up Your Development Environment

1. **Clone the repository**:
    ```bash
    git clone https://github.com/pteroca-com/panel.git
    cd panel
    ```

2. **Install dependencies**:
    Make sure you have [Composer](https://getcomposer.org/) installed. Run:
    ```bash
    composer install
    ```

3. **Set up the environment**:
    Create a `.env.local` file from `.env.example` and configure your database and environment variables:
    ```bash
    cp .env.example .env.local
    ```

4. **Run migrations**:
    ```bash
    php bin/console doctrine:migrations:migrate
    ```

5. **Start developing!**


## Helping with Translations
We want PteroCA to be accessible to users all over the world. If you're interested in helping translate PteroCA into more languages, you can contribute via our [Crowdin page](https://crowdin.com/project/pteroca). No coding skills are required—just your language expertise!

## License

By contributing to PteroCA, you agree that your contributions will be licensed under the [MIT License](https://github.com/pteroca-com/panel/blob/main/LICENSE).
