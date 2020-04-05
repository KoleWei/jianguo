define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'ph_zp/index' + location.search,
                    add_url: 'ph_zp/add',
                    table: 'zp',
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

                        {field: 'styles.name', title: __('Styles.name'), operate: 'like'},

                        {field: 'covorimage', title: __('Covorimage'), operate: false, events: Table.api.events.image, formatter: Table.api.formatter.image},

                        {
                            field: 'data', 
                            title: __('Data'), 
                            operate: false,
                            formatter: function(val, row, index) {
                                switch(row['type']) {
                                    case 'zp':
                                        return '<a target="_brank;" href="' + row['data'] + '"><img class="img-lg img-center" src="' + row['data'] + '"/></a>'
                                    case 'tx':
                                        return '<a target="_brank;" href="https://v.qq.com/x/page/' + row['data'] + '.html">点击查看视频</a>'
                                    default:
                                        break;
                                }
                            }
                        },

                        {field: 'type', title: __('Type'), searchList: {"zp":__('Type zp'),"sp":__('Type sp'),"tx":__('Type tx')}, formatter: Table.api.formatter.normal},
                        {field: 'check', title: __('Check'), searchList: {"y":__('Check y'),"n":__('Check n'),"t":__('Check t')}, formatter: Table.api.formatter.normal},
                        {field: 'read_num', title: __('Read_num'), operate:'RANGE'},
                        {field: 'style', title: __('类型(不要填写)'), visible: false},
                        {field: 'createtime', title: __('Createtime'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {
                            field: 'operate', 
                            title: __('Operate'), 
                            table: table, 
                            events: Table.api.events.operate, 
                            formatter: Table.api.formatter.operate,
                            buttons:[{
                                name: '删除作品',
                                text: __('删除作品'),
                                classname: 'btn btn-xs btn-info btn-ajax',
                                icon: 'fa fa-trash',
                                url: 'ph_zp/del',
                                confirm: '是否删除作品',
                                success: function (data, ret) {
                                    table.bootstrapTable('refresh');
                                },
                            }]
                        }
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);

            Controller.api.bindevent();
        },
        add: function () {
            Controller.api.bindevent();

            function setCurSelect () {
                var type = $("select option:selected").data("type");
                $(".data-box").hide();
                $("#" + type + "-data").show();
            }

            setCurSelect();
            $(document).on("change", "#c-style", setCurSelect)

        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        }
    };
    return Controller;
});