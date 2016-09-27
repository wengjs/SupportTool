<?php

namespace Wjs\Support;

use Wjs\Support\Ldap\Exception as LdapException;
use Wjs\Support\Ldap\SearchFilterBuilder;

class Ldap
{
    const SUCCESS = 0;

    /**
     * The resource of ldap_connect.
     *
     * @var resource
     */
    protected $connection = null;
    protected $is_bind = false;

    protected $hostname = null;
    protected $port = 389;
    protected $base_dn = null;
    protected $bind_dn = null;

    protected $filter_builder = null;

    public static function raw($filter)
    {
        return new Ldap(new SearchFilterBuilder($filter));
    }

    /**
     * Create a new instance.
     *
     * @return void
     */
    public function __construct(SearchFilterBuilder $filter_builder = null, array $config = array())
    {
        $this->filter_builder = empty($filter_builder) ? new SearchFilterBuilder : $filter_builder;

        if ( ! empty($config)) {
            $this->createConnection($config);
        }
    }

    public function resetFilter()
    {
        $this->filter_builder->reset();
        return $this;
    }

    public function createConnection(array $config)
    {
        $this->hostname = isset($config['hostname']) ? $config['hostname'] : $this->hostname;
        $this->port     = isset($config['port']) ? $config['port'] : $this->port;
        if ( ! empty($config['base_dn'])) {
            $this->base_dn  = $config['base_dn'];
            $this->bind_dn  = 'cn=users,'.$this->base_dn;
        }

        $this->connection = @ldap_connect($this->hostname, $this->port);
        ldap_set_option($this->connection, LDAP_OPT_PROTOCOL_VERSION, 3);

        if ( ! $this->connection) {
            throw LdapException('Could not connect to LDAP server: '.$this->hostname);
        }

        return $this;
    }

    public function get()
    {
        $result = @ldap_get_entries(
            $this->connection,
            @ldap_search(
                $this->connection,
                $this->base_dn,
                $this->filter_builder->toQueryString()
            )
        );
        $this->exitIfError();

        return $result;
    }

    public function bind($account, $password)
    {
        $this->is_bind = @ldap_bind(
            $this->connection,
            'uid='.$account.','.$this->bind_dn,
            $password
        );
        $this->exitIfError();

        return $this;
    }

    public function unbind()
    {
        if ($this->is_bind) {
            @ldap_unbind($this->connection);
            $this->is_bind = false;
        }
        return $this;
    }

    public function exitIfError()
    {
        if (Ldap::SUCCESS !== ldap_errno($this->connection)) {
            throw new LdapException(ldap_error($this->connection));
        }
    }

    public function where()
    {
        $this
            ->filter_builder
            ->organize('&', $this->getFomular(false, func_get_args()));

        return $this;
    }

    public function orWhere()
    {
        $this
            ->filter_builder
            ->organize('|', $this->getFomular(false, func_get_args()));

        return $this;
    }

    public function whereNot()
    {
        $this
            ->filter_builder
            ->organize('&', $this->getFomular(true, func_get_args()));

        return $this;
    }

    public function orWhereNot()
    {
        $this
            ->filter_builder
            ->organize('|', $this->getFomular(true, func_get_args()));

        return $this;
    }

    public function getFilterString()
    {
        return $this->filter_builder->toString();
    }

    public function getFilterQueryString()
    {
        return $this->filter_builder->toQueryString();
    }

    protected function getFomular($has_not_operator, array $args)
    {
        $callbacks = array(
            function (Ldap $ldap)
            {
                return $ldap->getFilterString();
            },
            function ($attribute, $value) use ($has_not_operator)
            {
                return !!$has_not_operator ?
                    SearchFilterBuilder::notEqual($attribute, $value) :
                    SearchFilterBuilder::equal($attribute, $value);
            },
            function ($attribute, $operator, $value)  use ($has_not_operator)
            {
                return !!$has_not_operator ?
                    SearchFilterBuilder::notCustom($attribute, $operator, $value) :
                    SearchFilterBuilder::custom($attribute, $operator, $value);
            },
        );

        if (empty($args)) {
            throw LdapException('Input Arguments Invalid.');
        }

        return call_user_func_array($callbacks[count($args)-1], $args);
    }

}
