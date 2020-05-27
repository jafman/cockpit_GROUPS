<?php

namespace Cockpit\Controller;

class Groups extends \Cockpit\AuthController {

    public function index() {

        if (!$this->module('cockpit')->hasaccess('cockpit', 'groups')) {
            return $this->helper('admin')->denyRequest();
        }

        $current = $this->user["_id"];
        $groups = $this->module('cockpit')->getGroups();

        return $this->render('groups:views/index.php', compact('current', 'groups'));
    }

    public function info() {
        return $this->render('groups:views/info.php', ['markdown' => $this->module('cockpit')->markdown]);
    }

    public function group($gid = null) {

        if (!$gid) {
            $gid = $this->group["_id"];
        }

        if (!$this->module('cockpit')->hasaccess('cockpit', 'groups')) {
            return $this->helper('admin')->denyRequest();
        }

        $group = $this->app->storage->findOne("cockpit/groups", ["_id" => $gid]);

        if (!$group) {
            return false;
        }

        $fields = $this->app->retrieve('config/groups/fields', null);

        return $this->render('groups:views/group.php', compact('group', 'gid', 'fields'));
    }

    public function create() {

        $collections = $this->module('collections')->collections();

        // defaults for the creation of a new group
        $group = [
            'group' => '', // group name
            'password' => '',
            'vars' => self::getGroupVars(),
            'admin' => false,
            'cockpit' => [
                'finder' => true,
                'rest' => true,
                'backend' => true
            ]
        ];

        return $this->render('groups:views/group.php', compact('group', 'collections'));
    }

    public function save() {

        if ($data = $this->param("group", false)) {

            $data["_modified"] = time();

            if (!isset($data['_id'])) {
                $data["_created"] = $data["_modified"];
            }

            $this->app->storage->save("cockpit/groups", $data);

            return json_encode($data);
        }

        return false;
    }

    public function remove() {

        if ($data = $this->param("group", false)) {

            // can't delete own group
            if ($data["_id"] != $this->user["_id"]) {

                $this->app->storage->remove("cockpit/groups", ["_id" => $data["_id"]]);

                return '{"success":true}';
            }
        }

        return false;
    }

    public function find() {

        $options = $this->param('options', []);

        $groups = $this->storage->find("cockpit/groups", $options)->toArray(); // get groups from db
        $count = (!isset($options['skip']) && !isset($options['limit'])) ? count($groups) : $this->storage->count("cockpit/groups", isset($options['filter']) ? $options['filter'] : []);
        $pages = isset($options['limit']) ? ceil($count / $options['limit']) : 1;
        $page = 1;

        if ($pages > 1 && isset($options['skip'])) {
            $page = ceil($options['skip'] / $options['limit']) + 1;
        }

        return compact('groups', 'count', 'pages', 'page');
    }
    
    public static function getGroupVars() : array {
        return [
            'finder.path'               =>'/storage',
            'finder.allowed_uploads'    => '*',
            'assets.path'               => '/storage/assets',
            'assets.allowed_uploads'    => '*',
            'assets.max_upload_size'    => '0',
            'media.path'                => '/storage/media'
        ];
    }
    
    public static function getGroupVarInfo($key) {
        return [
            'finder.allowed_uploads' => $file_extensions_info_text = 'list of file extensions like so: »jpg jpeg png« (without the quotes & without comma). Using asterisk (*) enables ALL fileextensions',
            'assets.allowed_uploads' => $file_extensions_info_text,
            'assets.max_upload_size' => 'Maximum size per file in BYTES (0 = no limit)',
        ][$key] ?? null;
    }

}
