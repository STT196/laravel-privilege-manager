# Changelog

All notable changes to this package will be documented in this file.

## [1.0.1] - 2026-05-01

### Added
- Native `saveUserPrivilege` helper for inserting or updating privilege rows in `tbl_user_privilege`
- `PrivilegeService::savePrivilege()` for package-level privilege writes with automatic cache clearing
- Documentation updates covering privilege storage and cache behavior

### Changed
- Clarified that privilege updates clear the user privilege cache automatically after save

## [1.0.0] - 2026-04-30

### Added
- Initial Composer package structure
- Security-hardened privilege service
- Route middleware and helper functions
- Configuration publishing support
- Documentation and usage examples
- Git repository structure and release tagging
