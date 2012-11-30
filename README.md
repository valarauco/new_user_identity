#New user identity Roundcube Plugin

new_user_identity plugin is based on Roundcube's [new_user_identity](https://github.com/roundcube/roundcubemail/tree/master/plugins/new_user_identity).

The original plugin had a problem when you use a LDAP Addressbook with 'user_specific' property and without 'bind_pass'.

##Changelog
###v0.1:
* Added 'user_specific' and 'bind_pass' validation
* Set 'bind_pass' because Session does not exist yet
* Replace for %fu and %u in 'bind_dn' because user does not exist yet

## Install
* Delete old new_user_identity if necessary
* Place this plugin folder into plugins directory of Roundcube (git clone or Download)
* Add new_user_identity to $rcmail_config['plugins'] in your Roundcube config
