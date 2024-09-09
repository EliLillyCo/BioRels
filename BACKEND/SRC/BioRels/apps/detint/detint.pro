TEMPLATE = app
CONFIG += console
CONFIG -= app_bundle
CONFIG -= qt
QMAKE_CXXFLAGS += -std=c++11
QMAKE_CXXFLAGS += -static-libstdc++

QMAKE_CXXFLAGS += -D_GLIBCXX_USE_CXX11_ABI=0

SOURCES +=   $(TG_DIR)/BACKEND/SRC/BioRels/apps/detint/main.cpp \
    $(TG_DIR)/BACKEND/SRC/BioRels/apps/detint/detint.cpp

LIBS        += -lz
INCLUDEPATH += $(TG_DIR)/BACKEND/SRC/BioRels//libs/common/
DEPENDPATH  += $(TG_DIR)/BACKEND/SRC/BioRels//libs/common/
INCLUDEPATH += $(TG_DIR)/BACKEND/SRC/BioRels//libs/common/headers/
INCLUDEPATH += $(TG_DIR)/BACKEND/SRC/BioRels//libs/common/sources/
INCLUDEPATH += $(TG_DIR)/BACKEND/SRC/BioRels//libs/iwlib2/
DEPENDPATH  += $(TG_DIR)/BACKEND/SRC/BioRels//libs/iwlib2/
INCLUDEPATH += $(TG_DIR)/BACKEND/SRC/BioRels//libs/gc3tk/
DEPENDPATH  += $(TG_DIR)/BACKEND/SRC/BioRels//libs/gc3tk/
DEPENDPATH  += $(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Foundational/iwmisc/
INCLUDEPATH += $(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Foundational/iwmisc/
DEPENDPATH  += $(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Molecule_Lib/
INCLUDEPATH += $(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Molecule_Lib/
DEPENDPATH  += $(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Foundational/cmdline/
INCLUDEPATH += $(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Foundational/cmdline/
INCLUDEPATH += $(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Foundational/cmdline_v2/
DEPENDPATH  += $(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Foundational/cmdline_v2/
DEPENDPATH  += $(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Foundational/iwstring/
INCLUDEPATH += $(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Foundational/iwstring/
DEPENDPATH  += $(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Foundational/iwaray/
INCLUDEPATH += $(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Foundational/iwaray/
DEPENDPATH  += $(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Foundational/iwcrex/
INCLUDEPATH += $(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Foundational/iwcrex/
DEPENDPATH  += $(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Foundational/iwbits/
INCLUDEPATH += $(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Foundational/iwbits/
DEPENDPATH  += $(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Foundational/mtrand/
INCLUDEPATH += $(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Foundational/mtrand/
DEPENDPATH  += $(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Foundational/data_source/
INCLUDEPATH += $(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Foundational/data_source/
DEPENDPATH  += $(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Foundational/accumulator/
INCLUDEPATH += $(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Foundational/accumulator/
DEPENDPATH  += $(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Foundational/iwqsort/
INCLUDEPATH += $(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Foundational/iwqsort/
DEPENDPATH  += $(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Foundational/xmlParser/
INCLUDEPATH += $(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Foundational/xmlParser/
DEPENDPATH  += $(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Foundational/include/
INCLUDEPATH += $(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Foundational/include/

CONFIG(debug, debug|release):  PRE_TARGETDEPS += $(TG_DIR)/BACKEND/SRC/BioRels/build/debug/libs/common/libprotint.a
CONFIG(release, debug|release):PRE_TARGETDEPS += $(TG_DIR)/BACKEND/SRC/BioRels/build/release/libs/common/libprotint.a
CONFIG(debug, debug|release):  PRE_TARGETDEPS += $(TG_DIR)/BACKEND/SRC/BioRels/build/debug/libs/iwlib2/libiwlib2.a
CONFIG(release, debug|release):PRE_TARGETDEPS += $(TG_DIR)/BACKEND/SRC/BioRels/build/release/libs/iwlib2/libiwlib2.a
CONFIG(debug, debug|release):  PRE_TARGETDEPS += $(TG_DIR)/BACKEND/SRC/BioRels/build/debug/libs/gc3tk/libgc3tk.a
CONFIG(release, debug|release):PRE_TARGETDEPS += $(TG_DIR)/BACKEND/SRC/BioRels/build/release/libs/gc3tk/libgc3tk.a

CONFIG(release, debug|release):LIBS += -L$(TG_DIR)/BACKEND/SRC/BioRels/build/release/libs/gc3tk/  -lgc3tk
CONFIG(debug, debug|release):  LIBS += -L$(TG_DIR)/BACKEND/SRC/BioRels/build/debug/libs/gc3tk/    -lgc3tk
CONFIG(release, debug|release):LIBS += -L$(TG_DIR)/BACKEND/SRC/BioRels/build/release/libs/iwlib2/ -liwlib2
CONFIG(debug, debug|release):  LIBS += -L$(TG_DIR)/BACKEND/SRC/BioRels/build/debug/libs/iwlib2/   -liwlib2
CONFIG(release, debug|release):LIBS += -L$(TG_DIR)/BACKEND/SRC/BioRels/build/release/libs/common/ -lprotint
CONFIG(debug, debug|release):  LIBS += -L$(TG_DIR)/BACKEND/SRC/BioRels/build/debug/libs/common/   -lprotint

HEADERS += \
    $(TG_DIR)/BACKEND/SRC/BioRels/apps/detint/detint.h







