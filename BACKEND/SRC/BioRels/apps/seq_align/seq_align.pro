TEMPLATE = app
CONFIG += console
CONFIG -= app_bundle
CONFIG -= qt
QMAKE_CXXFLAGS += -std=c++11
QMAKE_CXXFLAGS += -static-libstdc++

QMAKE_CXXFLAGS += -D_GLIBCXX_USE_CXX11_ABI=0


HEADERS += \
    $(TG_DIR)/BACKEND/SRC/BioRels/libs/gc3tk/gc3tk.h \
    $(TG_DIR)/BACKEND/SRC/BioRels/libs/gc3tk/headers/sequence/seqpair.h \
    $(TG_DIR)/BACKEND/SRC/BioRels/libs/gc3tk/headers/sequence/seqalign.h 
SOURCES += \
    $(TG_DIR)/BACKEND/SRC/BioRels/apps/seq_align/main.cpp
    $(TG_DIR)/BACKEND/SRC/BioRels/libs/gc3tk/gc3tk.cpp \
    $(TG_DIR)/BACKEND/SRC/BioRels/libs/gc3tk/sources/sequence/seqpair.cpp \
    $(TG_DIR)/BACKEND/SRC/BioRels/libs/gc3tk/sources/sequence/seqalign.cpp 





INCLUDEPATH += $(TG_DIR)/BACKEND/SRC/BioRels//libs/common/
DEPENDPATH += $(TG_DIR)/BACKEND/SRC/BioRels//libs/common/
INCLUDEPATH += $(TG_DIR)/BACKEND/SRC/BioRels//libs/common/headers/
INCLUDEPATH += $(TG_DIR)/BACKEND/SRC/BioRels//libs/common/sources/
CONFIG(debug, debug|release):  PRE_TARGETDEPS += $(TG_DIR)/BACKEND/SRC/BioRels/build/debug/libs/common/libprotint.a
CONFIG(release, debug|release):PRE_TARGETDEPS += $(TG_DIR)/BACKEND/SRC/BioRels/build/release/libs/common/libprotint.a


CONFIG(release, debug|release):LIBS += -L$(TG_DIR)/BACKEND/SRC/BioRels/build/release/libs/common/ -lprotint
CONFIG(debug, debug|release):  LIBS += -L$(TG_DIR)/BACKEND/SRC/BioRels/build/debug/libs/common/ -lprotint







