<?php
/**
 * New user identity
 *
 * Populates a new user's default identity from LDAP on their first visit.
 *
 * This plugin requires that a working public_ldap directory be configured.
 *
 * @version @package_version@
 * @author Kris Steinhoff
 * @author Manuel Delgado <manuel.delgado@ucr.ac.cr>
 *
 * Example configuration:
 *
 *  // The id of the address book to use to automatically set a new
 *  // user's full name in their new identity. (This should be an
 *  // string, which refers to the $rcmail_config['ldap_public'] array.)
 *  $rcmail_config['new_user_identity_addressbook'] = 'People';
 *
 *  // When automatically setting a new users's full name in their
 *  // new identity, match the user's login name against this field.
 *  $rcmail_config['new_user_identity_match'] = 'uid';
 */
class new_user_identity extends rcube_plugin
{
    public $task = 'login';

    private $ldap;

    function init()
    {
        $this->add_hook('user_create', array($this, 'lookup_user_name'));
    }

    function lookup_user_name($args)
    {
        $rcmail = rcmail::get_instance();

        if ($this->init_ldap($args['host'],$args['user'])) {
            $user_txt = substr($args['user'], 0, stripos($args['user'], '@'));
            $results = $this->ldap->search('*', $args['user'], 1, true);
            if (count($results->records) == 1) {
                $user_name  = is_array($results->records[0]['name']) ? $results->records[0]['name'][0] : $results->records[0]['name'];
                $user_email = is_array($results->records[0]['email']) ? $results->records[0]['email'][0] : $results->records[0]['email'];

                $args['user_name'] = $user_name;
                if (!$args['user_email'] && strpos($user_email, '@')) {
                    $args['user_email'] = rcube_idn_to_ascii($user_email);
                }
            }
        }
        return $args;
    }

    private function init_ldap($host, $user)
    {
        if ($this->ldap) {
            return $this->ldap->ready;
        }

        $rcmail = rcmail::get_instance();

        $addressbook = $rcmail->config->get('new_user_identity_addressbook');
        $ldap_config = (array)$rcmail->config->get('ldap_public');
        $match       = $rcmail->config->get('new_user_identity_match');

        if (empty($addressbook) || empty($match) || empty($ldap_config[$addressbook])) {
            return false;
        }

        $this->ldap = new new_user_identity_ldap_backend(
            $ldap_config[$addressbook],
            $rcmail->config->get('ldap_debug'),
            $rcmail->config->mail_domain($host),
            $match, $user);

        return $this->ldap->ready;
    }
}

class new_user_identity_ldap_backend extends rcube_ldap
{
    function __construct($p, $debug, $mail_domain, $search, $user)
    {
        if ($p['user_specific']) {
            if (empty($p['bind_pass'])) {
                $p['bind_pass'] = $_POST['_pass'];
            }
            $fu = $user;
            list($u, $d) = explode('@', $fu);

            $replaces = array( '%fu' => $fu, '%u' => $u);
            $p['bind_dn'] = strtr($p['bind_dn'], $replaces);
        }
        parent::__construct($p, $debug, $mail_domain);
        $this->prop['search_fields'] = (array)$search;
    }
}

