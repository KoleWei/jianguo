define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'cust_start/index' + location.search,
                    multi_url: 'cust_start/multi',
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
                        {field: 'id', title: __('Id'), visible:false},
                        {field: 'cust.nickname', title: __('摄影师昵称'), operate: 'like', },
                        {
                            field: 'cust.uname', title: __('摄影师名称'), operate: 'like', 
                            formatter: function(val, row, index) {
                                return row['cust']['uname'] || row['cust']['nickname'];
                            }
                        },
                        {field: 'styles.name', title: __('摄影类型'), operate: 'like', },
                        {field: 'needstar', title: __('提升星级'), operate: 'RANGE', },
                        {field: 'createtime', title: __('Createtime'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {
                            field: 'operate',
                             title: __('Operate'), 
                             table: table,
                              events: Table.api.events.operate,
                               formatter: Table.api.formatter.operate,
                               buttons:[{
                                name: 'success',
                                text: __('同意'),
                                title: __('同意'),
                                classname: 'btn btn-xs btn-success btn-magic btn-ajax',
                                icon: 'fa fa-magic',
                                url: 'cust_start/yunxu',
                                confirm: '是否允许提升星级',
                                visible: function(data) {
                                    return data['needstar'] >= 5;
                                },
                                success: function (data, ret) {
                                    Layer.alert(ret.msg);
                                    table.bootstrapTable('refresh');
                                },
                            },{
                                name: 'success',
                                text: __('拒绝'),
                                title: __('拒绝'),
                                classname: 'btn btn-xs btn-warning btn-magic btn-ajax',
                                icon: 'fa fa-magic',
                                url: 'cust_start/jujue',
                                confirm: '是否拒绝提升星级',
                                visible: function(data) {
                                    return data['needstar'] >= 5;
                                },
                                success: function (data, ret) {
                                    Layer.alert(ret.msg);
                                    table.bootstrapTable('refresh');
                                },
                            }]
                            
                            }
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