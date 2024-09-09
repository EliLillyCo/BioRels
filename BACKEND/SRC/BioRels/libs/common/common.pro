TARGET = protint
TEMPLATE = lib
CONFIG += staticlib
CONFIG -= app_bundle
CONFIG -= qt
QMAKE_CXXFLAGS += -std=c++11
QMAKE_CXXFLAGS += -static-libstdc++
QMAKE_CXXFLAGS += -D_GLIBCXX_USE_CXX11_ABI=0
DEFINES +="PROGRAM_VERSION=\"\\\"$$system(svnversion -n)\\\"\""
LIBS += -lm
HEADERS += \
    $(TG_DIR)/BACKEND/SRC/BioRels/libs/common/headers/statics/argcv.h \
$(TG_DIR)/BACKEND/SRC/BioRels/libs/common/headers/statics/atomdata.h \
$(TG_DIR)/BACKEND/SRC/BioRels/libs/common/headers/statics/dot.h \
#headers/statics/errors.h \
$(TG_DIR)/BACKEND/SRC/BioRels/libs/common/headers/statics/group.h \
$(TG_DIR)/BACKEND/SRC/BioRels/libs/common/headers/statics/grouplist.h \
$(TG_DIR)/BACKEND/SRC/BioRels/libs/common/headers/statics/ids.h \
$(TG_DIR)/BACKEND/SRC/BioRels/libs/common/headers/statics/intertypes.h \
$(TG_DIR)/BACKEND/SRC/BioRels/libs/common/headers/statics/link.h \
$(TG_DIR)/BACKEND/SRC/BioRels/libs/common/headers/statics/mol2data.h \
$(TG_DIR)/BACKEND/SRC/BioRels/libs/common/headers/statics/protExcept.h \
$(TG_DIR)/BACKEND/SRC/BioRels/libs/common/headers/statics/residuedata.h \
$(TG_DIR)/BACKEND/SRC/BioRels/libs/common/headers/statics/strutils.h \
    $(TG_DIR)/BACKEND/SRC/BioRels/libs/common/headers/math/coords.h \
    $(TG_DIR)/BACKEND/SRC/BioRels/libs/common/headers/math/coords_utils.h \
    $(TG_DIR)/BACKEND/SRC/BioRels/libs/common/headers/statics/objectpool.h \
    $(TG_DIR)/BACKEND/SRC/BioRels/libs/common/headers/statics/math_defs.h \
    $(TG_DIR)/BACKEND/SRC/BioRels/libs/common/headers/graph/edge.h \
    $(TG_DIR)/BACKEND/SRC/BioRels/libs/common/headers/graph/graph.h \
    $(TG_DIR)/BACKEND/SRC/BioRels/libs/common/headers/graph/vertex.h \
    $(TG_DIR)/BACKEND/SRC/BioRels/libs/common/headers/parser/ofstream_utils.h \
    $(TG_DIR)/BACKEND/SRC/BioRels/libs/common/headers/parser/string_convert.h \
    $(TG_DIR)/BACKEND/SRC/BioRels/libs/common/headers/graph/graphmatch.h \
    $(TG_DIR)/BACKEND/SRC/BioRels/libs/common/headers/graph/graphpair.h \
    $(TG_DIR)/BACKEND/SRC/BioRels/libs/common/headers/graph/graphclique.h \
    $(TG_DIR)/BACKEND/SRC/BioRels/libs/common/headers/math/matrix.h \
    $(TG_DIR)/BACKEND/SRC/BioRels/libs/common/headers/math/rigidbody.h \
    $(TG_DIR)/BACKEND/SRC/BioRels/libs/common/headers/math/grid.h \
    $(TG_DIR)/BACKEND/SRC/BioRels/libs/common/headers/math/box.h \
    $(TG_DIR)/BACKEND/SRC/BioRels/libs/common/headers/molecule/physprop.h \
    $(TG_DIR)/BACKEND/SRC/BioRels/libs/common/headers/molecule/macromole.h \
    $(TG_DIR)/BACKEND/SRC/BioRels/libs/common/headers/molecule/mmatom.h \
    $(TG_DIR)/BACKEND/SRC/BioRels/libs/common/headers/molecule/mmbond.h \
    $(TG_DIR)/BACKEND/SRC/BioRels/libs/common/headers/molecule/mmbond_utils.h \
    $(TG_DIR)/BACKEND/SRC/BioRels/libs/common/headers/molecule/mmchain.h \
    $(TG_DIR)/BACKEND/SRC/BioRels/libs/common/headers/molecule/mmresidue.h \
    $(TG_DIR)/BACKEND/SRC/BioRels/libs/common/headers/molecule/mmring.h \
    $(TG_DIR)/BACKEND/SRC/BioRels/libs/common/headers/molecule/mmresidue_utils.h \
    $(TG_DIR)/BACKEND/SRC/BioRels/libs/common/headers/molecule/mmring_utils.h \
    $(TG_DIR)/BACKEND/SRC/BioRels/libs/common/headers/statics/errors.h \
    $(TG_DIR)/BACKEND/SRC/BioRels/libs/common/headers/statics/protpool.h \
    $(TG_DIR)/BACKEND/SRC/BioRels/libs/common/headers/molecule/mmchain_utils.h \
    $(TG_DIR)/BACKEND/SRC/BioRels/libs/common/headers/molecule/macromole_utils.h \
    $(TG_DIR)/BACKEND/SRC/BioRels/libs/common/headers/statics/logger.h \
    $(TG_DIR)/BACKEND/SRC/BioRels/libs/common/headers/statics/log_policy_interface.h \
    $(TG_DIR)/BACKEND/SRC/BioRels/libs/common/headers/parser/readerbase.h \
    $(TG_DIR)/BACKEND/SRC/BioRels/libs/common/headers/parser/readMOL2.h \
    $(TG_DIR)/BACKEND/SRC/BioRels/libs/common/headers/sequence/seqbase.h \
    $(TG_DIR)/BACKEND/SRC/BioRels/libs/common/headers/sequence/seqchain.h \
    $(TG_DIR)/BACKEND/SRC/BioRels/libs/common/headers/sequence/seqstd.h \
    $(TG_DIR)/BACKEND/SRC/BioRels/libs/common/headers/sequence/sequence_utils.h \
    $(TG_DIR)/BACKEND/SRC/BioRels/libs/common/headers/sequence/uniprotentry.h \
    $(TG_DIR)/BACKEND/SRC/BioRels/libs/common/headers/parser/readPDB.h \
    $(TG_DIR)/BACKEND/SRC/BioRels/libs/common/headers/parser/readSDF.h \
    $(TG_DIR)/BACKEND/SRC/BioRels/libs/common/headers/proc/atomperception.h \
    $(TG_DIR)/BACKEND/SRC/BioRels/libs/common/headers/proc/atomstat.h \
    $(TG_DIR)/BACKEND/SRC/BioRels/libs/common/headers/molecule/mmatom_utils.h \
    $(TG_DIR)/BACKEND/SRC/BioRels/libs/common/headers/proc/multimole.h \
    $(TG_DIR)/BACKEND/SRC/BioRels/libs/common/headers/parser/readers.h \
    $(TG_DIR)/BACKEND/SRC/BioRels/libs/common/headers/parser/writerbase.h \
    $(TG_DIR)/BACKEND/SRC/BioRels/libs/common/headers/parser/writerMOL2.h \
    $(TG_DIR)/BACKEND/SRC/BioRels/libs/common/headers/math/rigidalign.h \
    $(TG_DIR)/BACKEND/SRC/BioRels/libs/common/headers/parser/writerPDB.h \
    $(TG_DIR)/BACKEND/SRC/BioRels/libs/common/headers/proc/matchmole.h \
    $(TG_DIR)/BACKEND/SRC/BioRels/libs/common/headers/parser/writerSDF.h \
    $(TG_DIR)/BACKEND/SRC/BioRels/libs/common/headers/math/grid_utils.h \
    $(TG_DIR)/BACKEND/SRC/BioRels/libs/common/headers/proc/volsurf.h \
    $(TG_DIR)/BACKEND/SRC/BioRels/libs/common/headers/statics/delsingleton.h \
    $(TG_DIR)/BACKEND/SRC/BioRels/libs/common/headers/statics/delmanager.h \
    $(TG_DIR)/BACKEND/SRC/BioRels/libs/common/headers/proc/bondperception.h \
    $(TG_DIR)/BACKEND/SRC/BioRels/libs/common/headers/inters/interdata.h \
    $(TG_DIR)/BACKEND/SRC/BioRels/libs/common/headers/inters/interobj.h \
    $(TG_DIR)/BACKEND/SRC/BioRels/libs/common/headers/proc/chainperception.h \
    $(TG_DIR)/BACKEND/SRC/BioRels/libs/common/headers/inters/inter_anionpi.h \
    $(TG_DIR)/BACKEND/SRC/BioRels/libs/common/headers/inters/inter_apolar.h \
    $(TG_DIR)/BACKEND/SRC/BioRels/libs/common/headers/inters/inter_arom.h \
    $(TG_DIR)/BACKEND/SRC/BioRels/libs/common/headers/inters/inter_atombase.h \
    $(TG_DIR)/BACKEND/SRC/BioRels/libs/common/headers/inters/inter_carbonylpi.h \
    $(TG_DIR)/BACKEND/SRC/BioRels/libs/common/headers/inters/inter_cationpi.h \
    $(TG_DIR)/BACKEND/SRC/BioRels/libs/common/headers/inters/inter_chpi.h \
    $(TG_DIR)/BACKEND/SRC/BioRels/libs/common/headers/inters/inter_halogenarom.h \
    $(TG_DIR)/BACKEND/SRC/BioRels/libs/common/headers/inters/inter_halogenbond.h \
    $(TG_DIR)/BACKEND/SRC/BioRels/libs/common/headers/inters/inter_hbond.h \
    $(TG_DIR)/BACKEND/SRC/BioRels/libs/common/headers/inters/inter_hbondhalogen.h \
    $(TG_DIR)/BACKEND/SRC/BioRels/libs/common/headers/inters/inter_ionic.h \
    $(TG_DIR)/BACKEND/SRC/BioRels/libs/common/headers/inters/inter_metal.h \
    $(TG_DIR)/BACKEND/SRC/BioRels/libs/common/headers/inters/inter_weakhbond.h \
    $(TG_DIR)/BACKEND/SRC/BioRels/libs/common/headers/inters/inter_atomarom.h \
    $(TG_DIR)/BACKEND/SRC/BioRels/libs/common/headers/inters/interprotlig.h \
    $(TG_DIR)/BACKEND/SRC/BioRels/libs/common/headers/inters/intercomplex.h \
    $(TG_DIR)/BACKEND/SRC/BioRels/libs/common/headers/inters/inter_hbondda.h \
    $(TG_DIR)/BACKEND/SRC/BioRels/libs/common/headers/inters/inter_hbondad.h \
    headers/sequence/seqnucl.h



SOURCES += \
    $(TG_DIR)/BACKEND/SRC/BioRels/libs/common/sources/statics/protExcept.cpp \
    $(TG_DIR)/BACKEND/SRC/BioRels/libs/common/sources/statics/strutils.cpp \
    $(TG_DIR)/BACKEND/SRC/BioRels/libs/common/sources/statics/argcv.cpp \
    $(TG_DIR)/BACKEND/SRC/BioRels/libs/common/sources/statics/atomdata.cpp \
    $(TG_DIR)/BACKEND/SRC/BioRels/libs/common/sources/statics/errors.cpp \
    $(TG_DIR)/BACKEND/SRC/BioRels/libs/common/sources/statics/protpool.cpp \
    $(TG_DIR)/BACKEND/SRC/BioRels/libs/common/sources/statics/logger.cpp \
    $(TG_DIR)/BACKEND/SRC/BioRels/libs/common/sources/statics/log_policy_interface.cpp \
  #  $(TG_DIR)/BACKEND/SRC/BioRels/libs/common/sources/statics/errors.cpp \
    $(TG_DIR)/BACKEND/SRC/BioRels/libs/common/sources/graph/edge.cpp \
    $(TG_DIR)/BACKEND/SRC/BioRels/libs/common/sources/graph/graph.cpp \
    $(TG_DIR)/BACKEND/SRC/BioRels/libs/common/sources/graph/vertex.cpp \
    $(TG_DIR)/BACKEND/SRC/BioRels/libs/common/sources/math/coords.cpp \
    $(TG_DIR)/BACKEND/SRC/BioRels/libs/common/sources/math/coords_utils.cpp \
    $(TG_DIR)/BACKEND/SRC/BioRels/libs/common/sources/math/rigidbody.cpp \
    $(TG_DIR)/BACKEND/SRC/BioRels/libs/common/sources/math/rigidalign.cpp \
    $(TG_DIR)/BACKEND/SRC/BioRels/libs/common/sources/math/grid.cpp \
    $(TG_DIR)/BACKEND/SRC/BioRels/libs/common/sources/math/box.cpp \
    $(TG_DIR)/BACKEND/SRC/BioRels/libs/common/sources/molecule/macromole.cpp \
    $(TG_DIR)/BACKEND/SRC/BioRels/libs/common/sources/molecule/mmatom.cpp \
    $(TG_DIR)/BACKEND/SRC/BioRels/libs/common/sources/molecule/mmbond.cpp \
    $(TG_DIR)/BACKEND/SRC/BioRels/libs/common/sources/molecule/mmbond_utils.cpp \
    $(TG_DIR)/BACKEND/SRC/BioRels/libs/common/sources/molecule/mmchain.cpp \
    $(TG_DIR)/BACKEND/SRC/BioRels/libs/common/sources/molecule/mmresidue.cpp \
    $(TG_DIR)/BACKEND/SRC/BioRels/libs/common/sources/molecule/mmring.cpp \
    $(TG_DIR)/BACKEND/SRC/BioRels/libs/common/sources/molecule/mmresidue_utils.cpp \
    $(TG_DIR)/BACKEND/SRC/BioRels/libs/common/sources/molecule/macromole_chain.cpp \
    $(TG_DIR)/BACKEND/SRC/BioRels/libs/common/sources/molecule/macromole_residue.cpp \
    $(TG_DIR)/BACKEND/SRC/BioRels/libs/common/sources/molecule/macromole_ring.cpp \
    $(TG_DIR)/BACKEND/SRC/BioRels/libs/common/sources/molecule/mmring_utils.cpp \
    $(TG_DIR)/BACKEND/SRC/BioRels/libs/common/sources/molecule/mmchain_utils.cpp \
    $(TG_DIR)/BACKEND/SRC/BioRels/libs/common/sources/molecule/macromole_utils.cpp \
    $(TG_DIR)/BACKEND/SRC/BioRels/libs/common/sources/molecule/mmatom_utils.cpp \
    $(TG_DIR)/BACKEND/SRC/BioRels/libs/common/sources/parser/readerbase.cpp \
    $(TG_DIR)/BACKEND/SRC/BioRels/libs/common/sources/parser/ofstream_utils.cpp \
    $(TG_DIR)/BACKEND/SRC/BioRels/libs/common/sources/parser/string_convert.cpp \
    $(TG_DIR)/BACKEND/SRC/BioRels/libs/common/sources/parser/readMOL2.cpp \
    $(TG_DIR)/BACKEND/SRC/BioRels/libs/common/sources/parser/readPDB.cpp \
    $(TG_DIR)/BACKEND/SRC/BioRels/libs/common/sources/parser/readSDF.cpp \
    $(TG_DIR)/BACKEND/SRC/BioRels/libs/common/sources/parser/readers.cpp \
    $(TG_DIR)/BACKEND/SRC/BioRels/libs/common/sources/parser/writerbase.cpp \
    $(TG_DIR)/BACKEND/SRC/BioRels/libs/common/sources/parser/writerMOL2.cpp \
    $(TG_DIR)/BACKEND/SRC/BioRels/libs/common/sources/parser/writerPDB.cpp \
    $(TG_DIR)/BACKEND/SRC/BioRels/libs/common/sources/parser/writerSDF.cpp \
    $(TG_DIR)/BACKEND/SRC/BioRels/libs/common/sources/sequence/seqbase.cpp \
    $(TG_DIR)/BACKEND/SRC/BioRels/libs/common/sources/sequence/seqchain.cpp \
    $(TG_DIR)/BACKEND/SRC/BioRels/libs/common/sources/sequence/seqstd.cpp \
    $(TG_DIR)/BACKEND/SRC/BioRels/libs/common/sources/sequence/sequence_utils.cpp \
    $(TG_DIR)/BACKEND/SRC/BioRels/libs/common/sources/sequence/uniprotentry.cpp \
    $(TG_DIR)/BACKEND/SRC/BioRels/libs/common/sources/proc/atom_perception/atomperception.cpp \
    $(TG_DIR)/BACKEND/SRC/BioRels/libs/common/sources/proc/atom_perception/atomstat.cpp \
    $(TG_DIR)/BACKEND/SRC/BioRels/libs/common/sources/proc/atom_perception/atom_perception_c.cpp \
    $(TG_DIR)/BACKEND/SRC/BioRels/libs/common/sources/proc/atom_perception/atom_perception_n.cpp \
    $(TG_DIR)/BACKEND/SRC/BioRels/libs/common/sources/proc/atom_perception/atom_perception_s.cpp \
    $(TG_DIR)/BACKEND/SRC/BioRels/libs/common/sources/proc/atom_perception/atom_perception_p.cpp \
    $(TG_DIR)/BACKEND/SRC/BioRels/libs/common/sources/proc/atom_perception/atom_perception_o.cpp \
    $(TG_DIR)/BACKEND/SRC/BioRels/libs/common/sources/proc/multimole.cpp \
    $(TG_DIR)/BACKEND/SRC/BioRels/libs/common/sources/proc/matchmole.cpp \
    $(TG_DIR)/BACKEND/SRC/BioRels/libs/common/sources/math/grid_utils.cpp \
    $(TG_DIR)/BACKEND/SRC/BioRels/libs/common/sources/proc/volsurf.cpp \
    $(TG_DIR)/BACKEND/SRC/BioRels/libs/common/sources/statics/delmanager.cpp \
    $(TG_DIR)/BACKEND/SRC/BioRels/libs/common/sources/proc/bondperception.cpp \
    $(TG_DIR)/BACKEND/SRC/BioRels/libs/common/sources/inters/interdata.cpp \
    $(TG_DIR)/BACKEND/SRC/BioRels/libs/common/sources/inters/interobj.cpp \
    $(TG_DIR)/BACKEND/SRC/BioRels/libs/common/sources/proc/chainperception.cpp \
    $(TG_DIR)/BACKEND/SRC/BioRels/libs/common/sources/inters/inter_anionpi.cpp \
    $(TG_DIR)/BACKEND/SRC/BioRels/libs/common/sources/inters/inter_apolar.cpp \
    $(TG_DIR)/BACKEND/SRC/BioRels/libs/common/sources/inters/inter_arom.cpp \
    $(TG_DIR)/BACKEND/SRC/BioRels/libs/common/sources/inters/inter_atombase.cpp \
    $(TG_DIR)/BACKEND/SRC/BioRels/libs/common/sources/inters/inter_carbonylpi.cpp \
    $(TG_DIR)/BACKEND/SRC/BioRels/libs/common/sources/inters/inter_cationpi.cpp \
    $(TG_DIR)/BACKEND/SRC/BioRels/libs/common/sources/inters/inter_chpi.cpp \
    $(TG_DIR)/BACKEND/SRC/BioRels/libs/common/sources/inters/inter_halogenarom.cpp \
    $(TG_DIR)/BACKEND/SRC/BioRels/libs/common/sources/inters/inter_halogenbond.cpp \
    $(TG_DIR)/BACKEND/SRC/BioRels/libs/common/sources/inters/inter_hbond.cpp \
    $(TG_DIR)/BACKEND/SRC/BioRels/libs/common/sources/inters/inter_hbondhalogen.cpp \
    $(TG_DIR)/BACKEND/SRC/BioRels/libs/common/sources/inters/inter_ionic.cpp \
    $(TG_DIR)/BACKEND/SRC/BioRels/libs/common/sources/inters/inter_metal.cpp \
    $(TG_DIR)/BACKEND/SRC/BioRels/libs/common/sources/inters/inter_weakhbond.cpp \
    $(TG_DIR)/BACKEND/SRC/BioRels/libs/common/sources/inters/inter_atomarom.cpp \
    $(TG_DIR)/BACKEND/SRC/BioRels/libs/common/sources/inters/interprotlig.cpp \
    $(TG_DIR)/BACKEND/SRC/BioRels/libs/common/sources/inters/intercomplex.cpp \
    $(TG_DIR)/BACKEND/SRC/BioRels/libs/common/sources/inters/inter_hbondda.cpp \
    $(TG_DIR)/BACKEND/SRC/BioRels/libs/common/sources/inters/inter_hbondad.cpp \
    $(TG_DIR)/BACKEND/SRC/BioRels/libs/common/sources/sequence/seqnucl.cpp





DISTFILES += \
    $(TG_DIR)/BACKEND/SRC/BioRels/libs/common/EXCEPTIONS.txt



