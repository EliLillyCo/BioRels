#!/bin/sh
source $TG_DIR/BACKEND/SCRIPT/SHELL/setenv.sh
php $TG_DIR/BACKEND/SCRIPT/WEBJOBS/wj_search_gene.php $1
