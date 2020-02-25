define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'cust/index' + location.search,
                    add_url: 'cust/add',
                    edit_url: 'cust/edit',
                    del_url: 'cust/del',
                    multi_url: 'cust/multi',
                    table: 'cust',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id')},
                        {field: 'logoimage', title: __('Logoimage'), events: Table.api.events.image, formatter: Table.api.formatter.image},
                        {field: 'openid', title: __('Openid')},
                        {field: 'nickname', title: __('Nickname')},
                        {field: 'uname', title: __('Uname')},
                        {field: 'phone', title: __('Phone')},
                        {field: 'is_photoer', title: __('Is_photoer'), searchList: {"y":__('Is_photoer y'),"n":__('Is_photoer n')}, formatter: Table.api.formatter.normal},
                        {field: 'is_teacher', title: __('Is_teacher'), searchList: {"y":__('Is_teacher y'),"n":__('Is_teacher n')}, formatter: Table.api.formatter.normal},
                        {field: 'is_agent', title: __('Is_agent'), searchList: {"y":__('Is_agent y'),"n":__('Is_agent n')}, formatter: Table.api.formatter.normal},
                        {field: 'is_tg', title: __('Is_tg'), searchList: {"y":__('Is_tg y'),"n":__('Is_tg n')}, formatter: Table.api.formatter.normal},
                        {field: 'createtime', title: __('Createtime'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        add: function () {
            Controller.api.bindevent();
        },
        edit: function () {
            Controller.api.bindevent();
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        }
    };
    return Controller;
});