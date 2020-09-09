# Moorexa Installer

> Before using this, ensure you have PHP installed on your machine and you can access it from the Terminal/Command Line Interface.

The installer makes it easy to download and install any stable release of moorexa programatically, and then registers moorexa to your system path so you can tap into all of the amazing features built into the ASSIST manager.

The installer also gives you the convinence for creating new projects without downloading a fresh copy of moorexa, and much more.


## How to use
1. Open your terminal or search for ("cmd" window users)
2. Check your PHP version with 
```bash
	php ---version
```
3. You should at least have php 7.2 installed.
4. Copy the code below to start installation;
```bash
	php -r "copy('https://raw.githubusercontent.com/wekiwork/moorexa-installer/master/installer.php', 'installer.php');"; php installer.php;
```
You can replace 'master' with a version number if you want to.