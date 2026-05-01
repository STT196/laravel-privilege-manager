# Documentation Index

Welcome to Laravel Privilege Manager documentation. Here's where to find everything you need.

## 📋 Getting Started

### [Installation & Setup Guide](INSTALLATION.md)
**Time: 15-30 minutes**
- System requirements
- Step-by-step installation
- Configuration
- Database setup
- Troubleshooting

### [Quick Start Guide](../README.md)
**Time: 5 minutes**
- What is this package?
- Key features
- Basic usage examples

## 📚 Learning

### [Usage Examples](USAGE_EXAMPLES.md)
**Real-world code examples**
- Controller patterns
- Blade view examples
- JavaScript integration
- API endpoints
- Testing examples

### [Migration Guide](MIGRATION.md)
**For existing privilege system users**
- Step-by-step migration
- Parallel running
- Rollback plan
- Common issues
- Timeline estimate

## 🔒 Security & Performance

### [Security & Performance Guide](SECURITY_AND_PERFORMANCE.md)
**Comprehensive documentation**
- Security features (rate limiting, validation, logging)
- Performance optimization (caching, batch operations)
- Best practices
- Monitoring & debugging
- Performance benchmarks

## 🛠️ Reference

### [Primary Key Configuration Guide](PRIMARY_KEY_CONFIGURATION.md)
**Database identifier customization**
- Configure `idtbl_user_privilege` and other primary keys
- Environment variable setup
- Legacy system migration
- Troubleshooting
- Quick reference table

### API Reference
See [README.md - API Reference](../README.md#api-reference) section for:
- Service methods
- Helper functions
- Middleware documentation

### Configuration
See [README.md - Configuration](../README.md#configuration) section

## 📊 What is this Package?

A production-ready privilege/permission management system for Laravel with:

✅ Menu-based access control  
✅ Enterprise-grade security  
✅ Performance optimizations  
✅ Easy integration  
✅ Comprehensive documentation  

## 🚀 Quick Navigation

| What do you want to do? | Where to go |
|----------------------|-----------|
| Install the package | [Installation Guide](INSTALLATION.md) |
| See code examples | [Usage Examples](USAGE_EXAMPLES.md) |
| Migrate from old system | [Migration Guide](MIGRATION.md) |
| Understand security | [Security & Performance](SECURITY_AND_PERFORMANCE.md) |
| Configure primary keys | [Primary Key Configuration](PRIMARY_KEY_CONFIGURATION.md) |
| Check API docs | [README - API Reference](../README.md#api-reference) |
| Configure settings | [README - Configuration](../README.md#configuration) |
| Test the package | [Usage Examples - Testing](USAGE_EXAMPLES.md#testing-examples) |
| Troubleshoot issues | [Troubleshooting](#troubleshooting-quick-reference) |

## 🎯 By Role

### For Developers
1. Start: [Installation Guide](INSTALLATION.md)
2. Learn: [Usage Examples](USAGE_EXAMPLES.md)
3. Deep dive: [Security & Performance](SECURITY_AND_PERFORMANCE.md)

### For DevOps/SysAdmins
1. Start: [Installation Guide](INSTALLATION.md)
2. Configure: [README - Configuration](../README.md#configuration)
3. Monitor: [Security & Performance - Monitoring](SECURITY_AND_PERFORMANCE.md#monitoring--debugging)

### For Project Managers
1. Overview: [README.md](../README.md)
2. Timeline: [Migration Guide - Timeline](MIGRATION.md#timeline)
3. Features: [README - Features](../README.md#features)

## 🔍 Quick Reference

### Helper Functions

```php
checkPrivilege($menuId, $action)              // Check privilege
canAccessMenu($menuId)                        // Check menu access
authorizePrivilege($menuId, $action)          // Authorize or abort
getMenuPrivileges($menuId)                    // Get all privileges
hasAnyPrivilege($menuId, $actions)            // Has ANY privilege
hasAllPrivileges($menuId, $actions)           // Has ALL privileges
getUserAccessibleMenus()                      // Get accessible menus
batchCheckPrivileges($checks)                 // Batch check
clearUserPrivilegeCache($userId)              // Clear cache
```

### Middleware Usage

```php
// In routes
Route::middleware('privilege:7')->get(...);                // Menu access
Route::middleware('privilege:7,add')->post(...);          // Specific action
Route::middleware(['auth', 'privilege:7,edit'])->put(...); // Combined
```

### Valid Actions

```php
'add'           // Create/Add action
'edit'          // Update/Edit action
'statuschange'  // Change status action
'remove'        // Delete/Remove action
```

## 🐛 Troubleshooting Quick Reference

### Common Issues

| Issue | Solution |
|-------|----------|
| `Class not found` | Run `composer dump-autoload` |
| `Privileges always false` | Check database records, verify user ID |
| `Permission denied on first run` | Clear cache: `php artisan cache:clear` |
| `Rate limiting blocking` | Adjust in config or disable for dev |
| `Slow privilege checks` | Enable caching, use batch operations |

See [SECURITY_AND_PERFORMANCE.md - Troubleshooting](SECURITY_AND_PERFORMANCE.md#troubleshooting-performance) for detailed solutions.

## 📞 Support

### Resources
- 📖 This documentation
- 💻 Code examples in [USAGE_EXAMPLES.md](USAGE_EXAMPLES.md)
- 🧪 Tests in `tests/` directory
- 🔧 Package configuration

### For Issues
1. Check [Troubleshooting](#troubleshooting-quick-reference) section
2. Review relevant documentation guide
3. Check code examples
4. Open an issue on repository

## 📈 Learning Path

**Beginner (1-2 hours):**
1. Read [README.md](../README.md)
2. Follow [Installation Guide](INSTALLATION.md)
3. Try [Quick Start examples](../README.md#quick-start)

**Intermediate (3-4 hours):**
1. Study [Usage Examples](USAGE_EXAMPLES.md)
2. Implement in your project
3. Review [API Reference](../README.md#api-reference)

**Advanced (2-3 hours):**
1. Deep dive [Security & Performance](SECURITY_AND_PERFORMANCE.md)
2. Optimize for your use case
3. Implement monitoring

**Total: 6-9 hours to become proficient**

## 🎓 Documentation Versions

- **Latest**: v1.0.0 (this documentation)
- Changelog: See git history
- Breaking changes: None yet

## 📝 Contributing to Docs

Found an issue or have suggestions? Contributions welcome!

## License

All documentation is provided under the MIT License. See [LICENSE](../LICENSE) file.

---

**Everything you need to know about Laravel Privilege Manager is here. Happy coding! 🚀**
