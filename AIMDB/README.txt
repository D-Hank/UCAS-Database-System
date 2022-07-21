AIMDB
=====

AIMDB是一个内存数据库系统

目录结构
========

system : AIMDB主目录，实现数据存储、访问、索引等功能，包括以下文件 

catalog.cc  datatype.h   errorlog.h   executor.h   global.cc  hashindex.cc  hashtable.cc  Makefile     mymemory.h  pbtree.cc  pbtreeindex.cc  rowtable.cc  runaimdb.cc
catalog.h   errorlog.cc  executor.cc  gcc_pf_p3.h  global.h   hashindex.h   hashtable.h   mymemory.cc  nodepref.h  pbtree.h   pbtreeindex.h   rowtable.h   schema.h

debug  : AIMDB组件调试目录，分别调试system目录下各个组件,包括以下文件

debug_catalog.cc   debug_errorlog.cc  debug_hashindex.cc  debug_mymemory.cc  debug_pbtreeindex.cc  debug_schema.cc
debug_datatype.cc  debug_executor.cc  debug_hashtable.cc  debug_pbtree.cc    debug_rowtable.cc

example: 本目录包含一个程序示例，演示AIMDB的使用，包括1个数据库框架和2个数据表文件

example_schema.txt example_table_1.tab example_table_2.tab

编译 & 测试
===========

作者推荐使用Ubuntu-64bit操作系统,内存不小于1GB

编译该程序，可在当前目录下运行如下命令：
	
	make

编译同时会产生一些调试程序供参考

测试该程序，可在当前目录下运行如下命令：

	python test.py

测试时，python脚本将依次运行各调试程序，作者已检查并保证程序正确性

运行
====

运行该程序，可在当前目录下运行如下命令：

	./runaimdb

演示用的runaimdb.cc源文件在example/可以找到

各个调试程序在debug目录下，也可单独运行

文档查阅
========

本系统使用doxygen自动产生说明文档，文档以网页形式呈现，相关网页在html目录，可用浏览器查看

doxygen  doxygen.conf

说明
====

作者将AIMDB提供给国科大数据库系统课程使用，以下提供简要说明

(1) 在本次实验中，完善executor.h, executor.cc中的空白部分；包括设计并实现Operator类及其子类和SelectQuery类

(2) 你只需要提交executor.h和executor.cc文件，评测环境和当前环境一致

(3) 调试和检测可以修改system/runaimdb.cc文件，并编译执行

(4) 请不要将本程序上传到GitHub等开源代码托管平台

