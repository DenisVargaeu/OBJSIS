# 📖 Contributing to OBJSIS V2

<div align="center">

[![Contributions Welcome](https://img.shields.io/badge/Contributions-Welcome-brightgreen?style=for-the-badge)](#how-to-contribute)
[![Open Issues](https://img.shields.io/github/issues/DenisVargaeu/OBJSIS?style=for-the-badge)](https://github.com/DenisVargaeu/OBJSIS/issues)
[![Pull Requests](https://img.shields.io/github/issues-pr/DenisVargaeu/OBJSIS?style=for-the-badge)](https://github.com/DenisVargaeu/OBJSIS/pulls)

**Thank you for considering contributing to OBJSIS V2!** 🙌

</div>

---

## 📋 Table of Contents

- [Code of Conduct](#-code-of-conduct)
- [How to Contribute](#-how-to-contribute)
- [Reporting Bugs](#-reporting-bugs)
- [Suggesting Features](#-suggesting-features)
- [Development Setup](#-development-setup)
- [Coding Standards](#-coding-standards)
- [Commit Guidelines](#-commit-guidelines)
- [Pull Request Process](#-pull-request-process)
- [Testing](#-testing)
- [Documentation](#-documentation)
- [Security Policy](#-security-policy)
- [Questions?](#-questions)

---

## 🤝 Code of Conduct

### Our Pledge

We are committed to providing a welcoming and inspiring community for all. Please read and adhere to our Code of Conduct:

**Be Respectful**
- Treat all community members with respect and dignity
- Welcome diverse perspectives and experiences
- Listen actively and engage constructively

**Be Constructive**
- Provide helpful and actionable feedback
- Focus on the issue, not the person
- Offer solutions along with criticism

**Be Inclusive**
- Use inclusive language
- Welcome contributions from all levels of experience
- Support newcomers and help them get started

**Prohibited Behavior**
- ❌ Harassment, discrimination, or abusive language
- ❌ Trolling or intentionally disruptive behavior
- ❌ Sharing private information without consent
- ❌ Any form of discrimination

**Violations**
Violations of the Code of Conduct will result in temporary or permanent bans. Report violations to: [contact email]

---

## 🎯 How to Contribute

### 💡 Ways You Can Help

| Contribution Type | Description |
|------------------|-------------|
| 🐛 **Bug Reports** | Report issues you encounter while using OBJSIS |
| 💭 **Feature Ideas** | Suggest new features or improvements |
| 📝 **Documentation** | Improve README, comments, or create tutorials |
| 🔍 **Code Review** | Review pull requests and provide feedback |
| 🧪 **Testing** | Test new features and report findings |
| 🎨 **UI/UX** | Design improvements and mockups |
| 🌐 **Localization** | Help translate to other languages |
| 📚 **Wiki & Guides** | Write tutorials and documentation |

---

## 🐛 Reporting Bugs

### Before You Report

1. **Search existing issues** - Your bug might already be reported
2. **Check documentation** - Solution might be in README or Wiki
3. **Test with latest version** - Bug might be already fixed
4. **Gather information** - Have details ready before reporting

### How to Report

**Click here to report a bug:** [New Bug Report](https://github.com/DenisVargaeu/OBJSIS/issues/new?template=bug_report.md)

### Bug Report Template

```markdown
## 🐛 Bug Description
Brief summary of the bug

## 📍 Location
Where did the bug occur? (Admin dashboard, API, KDS, etc.)

## 📝 Steps to Reproduce
1. First step
2. Second step
3. ...

## ✅ Expected Behavior
What should happen?

## ❌ Actual Behavior
What actually happened?

## 🖼️ Screenshots
Attach screenshots if applicable

## 🔧 Environment
- PHP Version: 
- MySQL Version:
- Web Server:
- Browser: 
- OS:

## 📋 Additional Context
Any other context?
```

### Good Bug Reports Include

✅ Clear, descriptive title  
✅ Steps to reproduce the issue  
✅ Expected vs. actual behavior  
✅ PHP and MySQL versions  
✅ Error logs (if available)  
✅ Screenshots or screen recording  
✅ Browser console errors  

### Bad Bug Reports

❌ "It doesn't work"  
❌ No reproduction steps  
❌ No environment information  
❌ Multiple unrelated issues in one report  
❌ Urgent language/demands  

---

## 💡 Suggesting Features

### Before You Suggest

1. **Check roadmap** - Feature might be planned
2. **Search discussions** - Similar idea might exist
3. **Consider scope** - Does it fit OBJSIS's purpose?
4. **Think about users** - Who benefits from this?

### How to Suggest

**Click here to suggest a feature:** [New Feature Request](https://github.com/DenisVargaeu/OBJSIS/issues/new?template=feature_request.md)

### Feature Request Template

```markdown
## 💡 Feature Request
What feature would you like to see?

## 🎯 Problem It Solves
What problem does this feature solve?

## ✨ Proposed Solution
How would you implement this?

## 🔄 Alternatives Considered
Other approaches you've considered?

## 👥 Impact
Who would benefit? (Admins, cooks, waiters, etc.)

## 📎 Additional Context
Links, screenshots, or references?
```

### Good Feature Suggestions

✅ Clear, descriptive title  
✅ Problem statement with context  
✅ Proposed solution details  
✅ User impact and benefits  
✅ Examples or mockups  
✅ Alignment with project goals  

---

## 🛠️ Development Setup

### Prerequisites

```bash
# Required
- PHP 7.4+
- MySQL 5.7+ or MariaDB
- Git
- Code editor (VS Code, PhpStorm, etc.)

# Optional
- Composer
- PHPUnit (for testing)
- XAMPP or Docker
```

### Local Development Environment

#### 1. Fork & Clone

```bash
# Fork the repository on GitHub
# Clone your fork
git clone https://github.com/YOUR-USERNAME/OBJSIS.git
cd OBJSIS

# Add upstream remote
git remote add upstream https://github.com/DenisVargaeu/OBJSIS.git
```

#### 2. Create Development Branch

```bash
# Get latest updates
git fetch upstream
git checkout -b feature/your-feature upstream/main
```

#### 3. Setup Local Database

```bash
# Create database
mysql -u root -p << EOF
CREATE DATABASE objsis_dev CHARACTER SET utf8mb4;
CREATE USER 'dev_user'@'localhost' IDENTIFIED BY 'dev_password';
GRANT ALL PRIVILEGES ON objsis_dev.* TO 'dev_user'@'localhost';
FLUSH PRIVILEGES;
EOF

# Configure config/db.php
cp config/db.php.example config/db.php
# Edit with your credentials
```

#### 4. Install OBJSIS

```bash
# Visit http://localhost/objsis/install.php
# Complete the setup wizard
```

#### 5. Enable Debug Mode

```php
// config/settings.php
define('DEBUG_MODE', true);
define('LOG_LEVEL', 'DEBUG');
```

---

## 📝 Coding Standards

**Language Composition:** 🔴 PHP (89.7%) • 🟠 CSS (8.6%) • 🟡 JavaScript (1.7%)

### PHP Code Style (89.7% of codebase)

#### Naming Conventions

```php
// Classes: PascalCase
class OrderManager { }

// Functions: snake_case
function get_active_orders() { }

// Constants: UPPER_SNAKE_CASE
define('MAX_ORDERS_PER_PAGE', 50);

// Variables: $snake_case
$user_id = 123;

// Private properties: $_snake_case
private $_connection;
```

#### Formatting (PSR-12 Compliant)

```php
<?php

// 4-space indentation
class UserManager {
    
    public function createUser($data) {
        if ($this->validateData($data)) {
            return $this->save($data);
        }
        
        return false;
    }
}

// Always use braces, even for single statements
if ($status === 'active') {
    $this->process();
}

// Not like this
if ($status === 'active')
    $this->process();
```

#### Documentation (PHPDoc)

```php
/**
 * Calculate total order amount including taxes
 *
 * @param int    $orderId  The order ID
 * @param float  $taxRate  Tax percentage (0-100)
 * 
 * @return float Total amount with tax
 * 
 * @throws Exception If order not found
 */
public function calculateTotal($orderId, $taxRate = 0) {
    // Implementation
}
```

#### Security Best Practices in PHP

```php
<?php

// ✅ Use prepared statements
$stmt = $pdo->prepare('SELECT * FROM orders WHERE id = ?');
$stmt->execute([$orderId]);

// ❌ Never do this
$sql = "SELECT * FROM orders WHERE id = $orderId";

// ✅ Validate and sanitize input
$email = filter_var($input, FILTER_SANITIZE_EMAIL);

// ✅ Use password_hash for passwords
$hashed = password_hash($password, PASSWORD_BCRYPT);

// ✅ Escape output
echo htmlspecialchars($userInput, ENT_QUOTES, 'UTF-8');
```

### CSS Code Style (8.6% of codebase)

```css
/* Use descriptive class names */
.order-item-list { }
.btn-primary { }
.modal-header { }

/* Not like this */
.list { }
.btn1 { }
.header { }

/* Organize properties: layout, display, spacing, color, other */
.button {
  display: inline-block;
  width: 100px;
  padding: 10px 15px;
  margin: 5px 0;
  color: #fff;
  background-color: #007bff;
  border-radius: 4px;
}

/* Group related selectors */
.btn-primary,
.btn-success {
  cursor: pointer;
  transition: all 0.3s;
}

/* Glassmorphic Design Pattern (OBJSIS V2 Theme) */
.glass-container {
  background: rgba(255, 255, 255, 0.1);
  backdrop-filter: blur(10px);
  border: 1px solid rgba(255, 255, 255, 0.2);
  border-radius: 15px;
}
```

### JavaScript Code Style (1.7% of codebase)

```javascript
// Use const/let, not var
const MAX_ITEMS = 100;
let currentOrder = null;

// camelCase for functions and variables
function calculateOrderTotal(items) {
    return items.reduce((sum, item) => sum + item.price, 0);
}

// 2-space indentation
const user = {
  id: 1,
  name: 'John Doe'
};

// Arrow functions for callbacks
items.map(item => item.price * item.quantity);

// Always use semicolons
const value = getValue();

// Use template literals
const message = `Order #${orderId} created at ${timestamp}`;
```

---

## 📝 Commit Guidelines

### Commit Message Format

```
<type>(<scope>): <subject>

<body>

<footer>
```

### Type

| Type | Description |
|------|-------------|
| **feat** | New feature |
| **fix** | Bug fix |
| **docs** | Documentation changes |
| **style** | Code style (no logic change) |
| **refactor** | Code refactoring |
| **perf** | Performance improvements |
| **test** | Tests additions/updates |
| **chore** | Build, dependencies, etc. |

### Scope

```
admin, api, kds, inventory, auth, addon, ui, db, dashboard, etc.
```

### Examples

```
feat(api): add endpoint for bulk order updates

fix(kds): resolve audio notification not playing on Safari

docs(readme): update installation instructions

refactor(auth): simplify session management logic

perf(dashboard): optimize chart rendering performance

test(inventory): add unit tests for stock calculation

style(css): update glassmorphic theme colors
```

### Commit Body

- Explain **what** and **why**, not **how**
- Reference issue numbers: `Fixes #123`
- Keep line length under 72 characters
- Be descriptive but concise

```
feat(addon): implement notification addon

Add real-time notification system for orders and inventory.
Integrates with WebSocket for live updates.

Features:
- Real-time order notifications
- Low stock alerts
- Customizable notification preferences

Fixes #45
```

---

## 🔄 Pull Request Process

### Before You Start

1. **Create an issue** first (unless it's small)
2. **Get feedback** on your approach
3. **Assign yourself** to avoid duplicate work
4. **Create branch** from latest `main`

### Step-by-Step

#### 1. Create Feature Branch

```bash
git checkout -b feature/my-feature upstream/main
git pull upstream main
```

#### 2. Make Changes

```bash
# Work on your feature
# Test thoroughly
# Commit with good messages
git add .
git commit -m "feat(scope): description"
```

#### 3. Keep Branch Updated

```bash
# Before pushing, sync with upstream
git fetch upstream
git rebase upstream/main
```

#### 4. Push to Your Fork

```bash
git push origin feature/my-feature
```

#### 5. Create Pull Request

- Go to GitHub repository
- Click "New Pull Request"
- Select your branch
- Fill out PR template

### Pull Request Template

```markdown
## 📝 Description
Briefly describe the changes

## 🎯 Type of Change
- [ ] Bug fix (non-breaking)
- [ ] New feature (non-breaking)
- [ ] Breaking change
- [ ] Documentation update

## 🔗 Related Issues
Closes #123

## ✅ Checklist
- [ ] Code follows style guidelines
- [ ] Comments added for complex logic
- [ ] Documentation updated
- [ ] No new warnings generated
- [ ] Tests pass locally
- [ ] Screenshots attached (if UI change)

## 📸 Screenshots (if applicable)
Attach before/after screenshots

## 🔄 Testing Instructions
How to test this change?

## 💬 Additional Context
Any additional information?
```

### PR Guidelines

✅ **DO**
- Keep PRs focused and reasonably sized
- Test your changes thoroughly
- Write clear commit messages
- Update documentation
- Be open to feedback
- Discuss major changes first

❌ **DON'T**
- Mix multiple unrelated changes
- Commit large formatting changes
- Remove unrelated code
- Include debug code or comments
- Submit before testing

### PR Review Process

1. **Automated checks** run (linting, tests)
2. **Code review** by maintainers
3. **Feedback** provided if needed
4. **Changes requested** - you make updates
5. **Approval** - PR ready to merge
6. **Merge** - Your contribution is live! 🎉

---

## 🧪 Testing

### Running Tests

```bash
# Run all tests
phpunit

# Run specific test
phpunit tests/OrderManagerTest.php

# Run with coverage
phpunit --coverage-html coverage/
```

### Writing Tests

```php
<?php
// tests/OrderManagerTest.php

class OrderManagerTest extends TestCase {
    
    private $orderManager;
    
    protected function setUp(): void {
        $this->orderManager = new OrderManager();
    }
    
    public function testCanCreateOrder() {
        $order = $this->orderManager->create([
            'table_id' => 1,
            'items' => []
        ]);
        
        $this->assertNotNull($order);
        $this->assertEquals('pending', $order->status);
    }
    
    public function testThrowsExceptionOnInvalidData() {
        $this->expectException(InvalidDataException::class);
        
        $this->orderManager->create([]);
    }
}
```

### Manual Testing Checklist

- [ ] Feature works as expected
- [ ] No errors in browser console
- [ ] No PHP warnings/errors
- [ ] Works on different browsers
- [ ] Responsive on mobile
- [ ] Accessibility tested
- [ ] Database operations work
- [ ] API endpoints respond correctly
- [ ] KDS displays properly
- [ ] Real-time updates work
- [ ] Light and dark themes both work

---

## 📚 Documentation

### What to Document

1. **Code Comments**
   - Complex logic
   - Non-obvious decisions
   - Business rules

2. **Function Documentation**
   - PHPDoc blocks
   - Parameter types
   - Return values

3. **README Updates**
   - New features
   - Configuration changes
   - Installation updates

4. **Wiki Articles**
   - Architecture decisions
   - Addon development
   - Deployment guides
   - API integration examples

### Documentation Example

```php
/**
 * Process incoming order and queue for kitchen
 *
 * This method validates the order, deducts inventory,
 * and sends notifications to the kitchen. Orders are
 * queued by urgency level and preparation time.
 *
 * @param Order  $order   The order object
 * @param array  $items   Order line items with quantities
 * @param bool   $urgent  Mark as urgent (default: false)
 *
 * @return OrderQueue|false The queued order or false on failure
 *
 * @throws InvalidOrderException If order is invalid
 * @throws InsufficientStockException If inventory depleted
 * @throws KitchenException If KDS communication fails
 *
 * @since 2.6.0
 * @see OrderValidator::validate()
 * @see InventoryManager::deductItems()
 */
public function processOrder(Order $order, array $items, $urgent = false) {
    // Implementation
}
```

---

## 🔒 Security Policy

### Reporting Security Issues

**⚠️ IMPORTANT: Do NOT open public issues for security vulnerabilities**

Instead:
1. Email security details to: [security email]
2. Include: vulnerability description, affected code, impact
3. Allow 48 hours for response
4. Don't disclose until patch is released

### Security Best Practices

When contributing:
- ✅ Validate all input (PHP layer)
- ✅ Use prepared statements for database queries
- ✅ Sanitize output to prevent XSS
- ✅ Follow OWASP guidelines
- ✅ Never commit credentials or API keys
- ✅ Use bcrypt for password hashing
- ✅ Implement rate limiting on APIs
- ✅ Add CSRF protection tokens
- ✅ Escape data properly based on context
- ✅ Keep dependencies updated
- ✅ Use HTTPS in production
- ✅ Implement proper session handling

### Security Checklist for PRs

- [ ] No hardcoded credentials or secrets
- [ ] Input validation on all user inputs
- [ ] SQL injection prevention (prepared statements)
- [ ] XSS protection (output escaping)
- [ ] CSRF tokens present where needed
- [ ] Authentication required for sensitive operations
- [ ] Authorization checks implemented
- [ ] Error messages don't leak sensitive info
- [ ] Rate limiting considered
- [ ] Dependencies are up-to-date

---

## ❓ Questions?

### Getting Help

- 📖 **Read Documentation** - Check README and Wiki first
- 💬 **GitHub Discussions** - Ask community questions
- 🐛 **GitHub Issues** - Report bugs or request features
- 📧 **Contact Developer** - For complex inquiries

### Resources

- [GitHub Help](https://help.github.com)
- [Git Basics](https://git-scm.com/book/en/v2)
- [PHP Best Practices](https://www.php-fig.org/)
- [MySQL Documentation](https://dev.mysql.com/doc/)
- [OWASP Security Guidelines](https://owasp.org/)

---

## 🎉 Recognition

Contributors are recognized in:
- ✨ GitHub contributors list
- 📄 CHANGELOG.md
- 🏆 Hall of Fame (significant contributions)
- 👏 README.md acknowledgments section

---

<div align="center">

### 🙏 Thank You!

Your contributions make OBJSIS V2 better for everyone.

**Happy contributing!** 🚀

[**Report Bug**](https://github.com/DenisVargaeu/OBJSIS/issues/new?template=bug_report.md) • [**Suggest Feature**](https://github.com/DenisVargaeu/OBJSIS/issues/new?template=feature_request.md) • [**View Issues**](https://github.com/DenisVargaeu/OBJSIS/issues)

</div>
