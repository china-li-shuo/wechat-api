<?php

namespace app\lib\enum;

/**
 * 接口访问权限枚举
 * 这种权限涉及是逐级式
 * 简单，但不够灵活
 * 最完整的权限控制方式是作用域列表式权限
 * 给每个令牌划分到一个SCOPE作用域，每个作用域
 * 可访问多个接口
 */
class ScopeEnum
{
    //普通用户
    const User = 16;
    //班级成员
    const Student  = 32;
    //班级老师
    const Teacher  = 64;


}