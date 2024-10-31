<p align="center"><img src="../wporgassets/banner-772x250.png" /></p>

<h1 align="center"> Revenue Generator by Laterpay </h1>

### Table of Contents
- [Development Notes](#development-notes)
- [Folder / File Structure](#folder--file-structure)

## Development Notes

Classes used at various stages, specific to their functionality is added here, you should be able to get a clear idea based on class name, if any feature seems to be not in expected class, please open up a issue/pull-request to notify the development team.

## Folder / File Structure

```text
inc
├── classes
│   ├── class-admin.php             ( Admin area features )
│   ├── class-assets.php            ( Plugin static assets management )
│   ├── class-categories.php        ( Handle term related features )
│   ├── class-client-account.php    ( Handle merchant account validation )
│   ├── class-config.php            ( Common configuration used across plugin )
│   ├── class-frontend-post.php     ( Code to handle frontend view of the post and paywall )
│   ├── class-plugin.php            ( Initialize all required classes and setup common constants)
│   ├── class-post-types.php        ( Handle custom CPT registered by plugin )
│   ├── class-utility.php           ( Common utility methods )
│   ├── class-view.php              ( Handle template and data rendering )
│   └── post-types                  ( Features specific to CPT )
│       ├── class-base.php
│       ├── class-paywall.php
│       ├── class-subscription.php
│       └── class-time-pass.php
├── helpers
│   └── autoloader.php              ( Autoloader to require our classes )
└── traits
    └── trait-singleton.php         ( Implements Singleton pattern in any used class, check class doc for more info )
```
