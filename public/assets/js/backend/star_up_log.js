define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'star_up_log/index' + location.search,
                    add_url: 'star_up_log/add',
                    edit_url: 'star_up_log/edit',
                    del_url: 'star_up_log/del',
                    multi_url: 'star_up_log/multi',
                    table: 'star_up_log',
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
                        {field: 'status', title: __('Status'), searchList: {"y":__('Status y'),"n":__('Status n')}, formatter: Table.api.formatter.status},
                        {field: 'createtime', title: __('Createtime'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'cust.nickname', title: __('Cust.nickname')},
                        {field: 'cust.uname', title: __('Cust.uname')},
                        {field: 'styles.name', title: __('Styles.name')},
                        {field: 'starup.id', title: __('Starup.id')},
                        {field: 'starup.needstar', title: __('Starup.needstar')},
                        {field: 'starup.step', title: __('Starup.step')},
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