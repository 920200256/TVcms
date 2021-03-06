<?php
/**
 * Created by bianquan
 * User: ZhuYunlong
 * Email: 920200256@qq.com
 * Date: 2019/1/21
 * Time: 21:54
 */

namespace app\admin\controller;
use app\admin\model\Role as RoleModel;
use app\admin\service\Role as RoleService;
use app\common\validate\PagingParameter;
use app\lib\exception\AuthException;
use app\lib\exception\RepeatException;
use app\lib\exception\ResourcesException;
use app\lib\Response;
use think\Cache;

class Role extends BaseController
{
    /**
     * @Api(获取角色列表,2,GET)
     */
    public function getList($name='',$page=1,$limit=10,$order='desc',$sort='create_time') {
        (new PagingParameter())->goCheck();
        $list = RoleModel::getList($name,$page,$limit,$order,$sort);
        return new Response(['data'=>$list]);
    }

    /**
     * @Api(更新指定角色信息,3,POST)
     */
    public function update($role_id,$role_name,$role_desc,$write_auth,$menus) {
        $roleCheck = RoleModel::get([
            'role_id' => ['<>',$role_id],
            'role_name'=>$role_name
        ]);
        if(!empty($roleCheck)) {
            throw new RepeatException(['msg'=>$role_name.'管理员已存在']);
        }
        $role = RoleModel::get($role_id);
        if(empty($role)) {
            throw new ResourcesException();
        }
        if($role->role_name == 'admin') {
            throw new AuthException(['msg'=>'超级管理员角色不能编辑']);
        }
        $role->role_name = $role_name;
        $role->role_desc = $role_desc;
        $role->write_auth = $write_auth;
        $role->save();
        $result = RoleService::updateRoleMenus($role_id,$menus);
        return new Response(['data'=>$result]);
    }

    /**
     * @Api(创建新角色,3,POST)
     */
    public function create($role_name,$role_desc,$write_auth,$menus) {
        $role = RoleModel::get(['role_name'=>$role_name]);
        if(!empty($role)) {
            throw new RepeatException(['msg'=>$role_name.'管理员已存在']);
        }
        $role = RoleModel::create([
            'role_name' => $role_name,
            'role_desc' => $role_desc,
            'write_auth' => $write_auth
        ]);
        $roleID = $role->role_id;
        RoleService::createRoleMenus($roleID,$menus);
        return new Response(['data'=>$role]);
    }

    /**
     * @Api(删除指定角色,3,POST)
     */
    public function delete($role_id) {
        $role = RoleModel::get($role_id);
        if(empty($role)) {
            throw new ResourcesException();
        }
        if($role->role_name == 'admin') {
            throw new AuthException(['msg'=>'超级管理员角色不能删除']);
        }
        $role->delete();
        RoleService::deleteRoleMenus($role_id);
        return new Response();
    }

    /**
     * @Api(通过名称查找一个角色,3,POST)
     */
    public function findByName($name) {
        $role = RoleModel::get(['role_name'=>$name]);
        if(empty($role)) {
            throw new ResourcesException();
        }
        return new Response(['data'=>$role]);
    }

}