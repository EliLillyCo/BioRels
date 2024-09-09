TARGET = gc3tk
TEMPLATE = lib
CONFIG += staticlib
CONFIG -= app_bundle
CONFIG -= qt
QMAKE_CXXFLAGS += -std=c++11

QMAKE_CXXFLAGS += -D_GLIBCXX_USE_CXX11_ABI=0


DEFINES +="PROGRAM_VERSION=\"\\\"$$system(svnversion -n)\\\"\""

INCLUDEPATH += $(TG_DIR)/BACKEND/SRC/BioRels//libs/common/
INCLUDEPATH += $(TG_DIR)/BACKEND/SRC/BioRels//libs/common/headers/
LIBS +=  -lz




CONFIG(release, debug|release):PRE_TARGETDEPS +=  $(TG_DIR)/BACKEND/SRC/BioRels/build/release/libs/common/libprotint.a
CONFIG(release, debug|release):PRE_TARGETDEPS +=  $(TG_DIR)/BACKEND/SRC/BioRels/build/release/libs/iwlib2/libiwlib2.a
CONFIG(debug, debug|release):  PRE_TARGETDEPS += $(TG_DIR)/BACKEND/SRC/BioRels/build/debug/libs/common/libprotint.a


CONFIG(release, debug|release):LIBS += -L$(TG_DIR)/BACKEND/SRC/BioRels/build/release/libs/common/ -lprotint
CONFIG(release, debug|release):LIBS += -L$(TG_DIR)/BACKEND/SRC/BioRels/build/release/libs/iwlib2/ -liwlib2
CONFIG(debug, debug|release):LIBS += -L$(TG_DIR)/BACKEND/SRC/BioRels/build/debug/libs/common/ -lprotint
CONFIG(debug, debug|release):LIBS += -L$(TG_DIR)/BACKEND/SRC/BioRels/build/debug/libs/iwlib2/ -liwlib2

HEADERS += \
    $(TG_DIR)/BACKEND/SRC/BioRels/libs/gc3tk/gc3tk.h \
    $(TG_DIR)/BACKEND/SRC/BioRels/libs/gc3tk/headers/molecule/hetentry.h \
    $(TG_DIR)/BACKEND/SRC/BioRels/libs/gc3tk/headers/molecule/hetmanager.h \
    $(TG_DIR)/BACKEND/SRC/BioRels/libs/gc3tk/headers/molecule/hetinput.h \
    $(TG_DIR)/BACKEND/SRC/BioRels/libs/gc3tk/headers/sequence/seqpair.h \
    $(TG_DIR)/BACKEND/SRC/BioRels/libs/gc3tk/headers/sequence/seqalign.h \
    $(TG_DIR)/BACKEND/SRC/BioRels/libs/gc3tk/headers/proc/protalign.h \
    $(TG_DIR)/BACKEND/SRC/BioRels/libs/gc3tk/headers/proc/matchaa.h \
    $(TG_DIR)/BACKEND/SRC/BioRels/libs/gc3tk/headers/proc/matchligand.h \
    $(TG_DIR)/BACKEND/SRC/BioRels/libs/gc3tk/headers/proc/matchresidue.h \
    $(TG_DIR)/BACKEND/SRC/BioRels/libs/gc3tk/headers/proc/matchtemplate.h \
    $(TG_DIR)/BACKEND/SRC/BioRels/libs/gc3tk/headers/proc/pugiconfig.hpp \
    $(TG_DIR)/BACKEND/SRC/BioRels/libs/gc3tk/headers/proc/pugixml.hpp \
    $(TG_DIR)/BACKEND/SRC/BioRels/libs/gc3tk/headers/molecule/pdbentry_utils.h

SOURCES += \
    $(TG_DIR)/BACKEND/SRC/BioRels/libs/gc3tk/gc3tk.cpp \
    $(TG_DIR)/BACKEND/SRC/BioRels/libs/gc3tk/sources/molecule/hetentry.cpp \
    $(TG_DIR)/BACKEND/SRC/BioRels/libs/gc3tk/sources/molecule/hetmanager.cpp \
    $(TG_DIR)/BACKEND/SRC/BioRels/libs/gc3tk/sources/molecule/hetinput.cpp \
    $(TG_DIR)/BACKEND/SRC/BioRels/libs/gc3tk/sources/sequence/seqpair.cpp \
    $(TG_DIR)/BACKEND/SRC/BioRels/libs/gc3tk/sources/sequence/seqalign.cpp \
    $(TG_DIR)/BACKEND/SRC/BioRels/libs/gc3tk/sources/proc/protalign.cpp \
    $(TG_DIR)/BACKEND/SRC/BioRels/libs/gc3tk/sources/proc/matchaa.cpp \
    $(TG_DIR)/BACKEND/SRC/BioRels/libs/gc3tk/sources/proc/matchligand.cpp \
    $(TG_DIR)/BACKEND/SRC/BioRels/libs/gc3tk/sources/proc/matchresidue.cpp \
    $(TG_DIR)/BACKEND/SRC/BioRels/libs/gc3tk/sources/proc/matchtemplate.cpp \
    $(TG_DIR)/BACKEND/SRC/BioRels/libs/gc3tk/sources/proc/pugixml.cpp \
    $(TG_DIR)/BACKEND/SRC/BioRels/libs/gc3tk/sources/molecule/pdbentry_utils.cpp
