define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'yuyue/index' + location.search,
                    edit_url: 'yuyue/edit',
                    del_url: 'yuyue/del',
                    multi_url: 'yuyue/multi',
                    table: 'yuyue',
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
                        {field: 'cust.nickname', title: __('Cust.nickname'), operate: 'like', visible: false},
                        {
                            field: 'cust.uname', 
                            title: __('Cust.uname'), 
                            operate: 'like',
                            formatter: function(val, row, index) {
                                return row['cust']['uname'] || row['cust']['nickname'];
                            }
                        },
                        {field: 'styles.name', title: __('Styles.name'), operate: 'like'},

                        {field: 'id', title: __('Id'), visible: false, operate:false },
                        {field: 'name', title: __('Name'), operate: 'like'},
                        {field: 'phone', title: __('Phone'), operate: 'like'},
                        {field: 'msg', title: __('Msg'), operate: 'like'},
                        
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