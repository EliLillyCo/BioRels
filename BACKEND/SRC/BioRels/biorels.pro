TEMPLATE = subdirs
SUBDIRS =  common seq_align 
QMAKE_CXXFLAGS += -std=c++11
QMAKE_CXXFLAGS += -static-libstdc++
QMAKE_CXXFLAGS += -D_GLIBCXX_USE_CXX11_ABI=0

common.subdir=libs/common

seq_align.subdir=apps/seq_align
seq_align.depends=gc3tk
