# Contributing to OBJSIS V2

Thank you for your interest in contributing to OBJSIS V2!

## 🚀 Getting Started

1. **Fork the repository**
2. **Clone your fork**
   ```bash
   git clone https://github.com/YOUR-USERNAME/objsis-v2.git
   cd objsis-v2
   ```

3. **Set up your development environment**
   - Install XAMPP or similar (PHP 7.4+, MySQL)
   - Copy `config/db.example.php` to `config/db.php`
   - Update database credentials in `config/db.php`
   - Run `install.php` in your browser

4. **Create a branch**
   ```bash
   git checkout -b feature/your-feature-name
   ```

## 📝 Development Guidelines

### Code Style
- Use **4 spaces** for indentation
- Follow **PSR-12** coding standards for PHP
- Use meaningful variable and function names
- Add comments for complex logic

### Database Changes
- Never commit `config/db.php` (it's in `.gitignore`)
- Document schema changes in `sql/schema.sql`
- Create migration scripts if needed

### Testing
- Test all changes locally before committing
- Verify both light and dark themes work
- Test on different screen sizes
- Check all user roles (admin, cook, waiter)

## 🐛 Reporting Bugs

Please include:
- Steps to reproduce
- Expected behavior
- Actual behavior
- Screenshots if applicable
- PHP/MySQL versions

## 💡 Feature Requests

- Check existing issues first
- Describe the feature clearly
- Explain the use case
- Consider backward compatibility

## 📦 Pull Request Process

1. Update README.md if needed
2. Test thoroughly
3. Commit with clear messages:
   ```
   feat: Add coupon expiration feature
   fix: Resolve login PIN validation bug
   docs: Update installation guide
   ```

4. Push to your fork
5. Create a Pull Request with:
   - Clear title
   - Description of changes
   - Related issue numbers

## 🎯 Priority Areas

We especially welcome contributions in:
- Multi-language support
- Database backup/restore
- Mobile responsiveness
- Performance optimization
- Security enhancements

## 📄 License

By contributing, you agree that your contributions will be licensed under the same license as the project.

## 💬 Questions?

Feel free to open an issue for discussion!

---

**Happy Coding! 🚀**
