define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'cust/index' + location.search,
                    edit_url: 'cust/edit',
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
                        {field: 'id', title: __('Id'), visible:false},
                        {field: 'logoimage', title: __('Logoimage'), events: Table.api.events.image, formatter: Table.api.formatter.image},
                        {field: 'openid', title: __('Openid'), visible: false, operate:'like'},
                        {field: 'nickname', title: __('Nickname'), operate:'like'},
                        {field: 'uname', title: __('Uname'), operate:'like'},
                        {field: 'phone', title: __('Phone'), operate:'like'},
                        {field: 'is_photoer', title: __('Is_photoer'), searchList: {"y":__('Is_photoer y'),"n":__('Is_photoer n')}, formatter: Table.api.formatter.normal},
                        {field: 'is_teacher', title: __('Is_teacher'), searchList: {"y":__('Is_teacher y'),"n":__('Is_teacher n')}, formatter: Table.api.formatter.normal},
                        {
                            field: 'is_agent', 
                            title: __('Is_agent'), 
                            searchList: {"y":__('Is_agent y'),"n":__('Is_agent n')}, 
                            formatter: function(val, row, index) {
                                if (val == 'y') {
                                    if (row['is_agent_vip'] == 'y'){
                                        return '<span style="color:red;">核心</span>';
                                    }else {
                                        return '<span style="color:green;">普通</span>';
                                    }
                                }
                                return '否';
                            }
                        },
                        {field: 'is_agent_vip', title: __('是否核心经纪人'), searchList: {"y":__('是'),"n":__('否')}, visible: false, formatter: Table.api.formatter.normal},
                        {field: 'is_tg', title: __('Is_tg'), searchList: {"y":__('Is_tg y'),"n":__('Is_tg n')}, formatter: Table.api.formatter.normal},
                        {field: 'createtime', title: __('Createtime'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {
                            field: 'operate', title: __('Operate'), 
                            table: table, events: Table.api.events.operate, 
                            formatter: Table.api.formatter.operate,
                            buttons:[{
                                name: 'detail',
                                text: __('查看星级'),
                                title: __('查看星级'),
                                classname: 'btn btn-xs btn-primary btn-dialog',
                                url: 'cust/star',
                                visible: function (row) {
                                    return true;
                                }
                            },{
                                name: 'account',
                                text: __('设置账号'),
                                title: __('设置账号'),
                                classname: 'btn btn-xs btn-primary btn-dialog',
                                url: 'cust/account',
                                visible: function (row) {
                                    return true;
                                }
                            },{
                                name: 'ordertotal',
                                text: __('订单统计'),
                                title: __('订单统计'),
                                classname: 'btn btn-xs btn-primary btn-dialog',
                                url: 'cust/ordertotal',
                                visible: function (row) {
                                    return (row['is_agent'] == 'y') || (row['is_photoer'] == 'y');
                                }
                            },{
                                name: 'createzp',
                                text: __('上传作品'),
                                title: __('上传作品'),
                                classname: 'btn btn-xs btn-primary btn-dialog',
                                url: 'cust/createzp',
                                visible: function (row) {
                                    return row['is_photoer'] == 'y';
                                }
                            }]
                        
                        }
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);


            $(document).on('click', '#notify', function (e){
                e.preventDefault();
                var ids = Table.api.selectedids(table);
                Fast.api.open('/admin/cust/notify?ids=' + ids.join(','), __('修改'), );
            })
        },
        star: function () {
            Controller.api.bindevent();
        },
        notify: function () {
            Controller.api.bindevent();
        },
        setstar: function () {
            Controller.api.bindevent();
        },
        edit: function () {
            Controller.api.bindevent();
        },
        account: function () {
            Controller.api.bindevent();
        },
        eaccount: function () {
            Controller.api.bindevent();
        },
        ordertotal: function () {
            Controller.api.bindevent();
        },
        createzp: function () {
            Controller.api.bindevent();

            // $('#plupload-data').data('upload-success', function(b,d) {
            //     $('#c-covorimage').val(d['data']['thumbnail']);
            //     $('#c-covorimage').trigger("change").trigger("validate");
            // })

            // $('#plupload-data').on('change', function(res) {
            //     console.log(res);
            // })

            
            function setCurSelect () {
                var type = $("select option:selected").data("type");
                $(".data-box").hide();
                $("." + type + "-data").show();
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