TARGET = iwlib2
TEMPLATE = lib
CONFIG += staticlib
CONFIG -= app_bundle
CONFIG -= qt
QMAKE_CXXFLAGS += -std=c++11
QMAKE_CXXFLAGS += -static-libstdc++

QMAKE_CXXFLAGS += -D_GLIBCXX_USE_CXX11_ABI=0
LIBS += -lz

DEFINES+= UNIX=1
DEFINES+= IW_TWO_PHASE_TEMPLATES=1
DEFINES+= NEED_EXTERN_OPT
DEPENDPATH += $(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Foundational/iwmisc/
INCLUDEPATH +=$(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Foundational/iwmisc/
DEPENDPATH += $(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Molecule_Lib/
INCLUDEPATH +=$(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Molecule_Lib/
DEPENDPATH += $(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Foundational/cmdline/
INCLUDEPATH +=$(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Foundational/cmdline/
INCLUDEPATH +=$(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Foundational/cmdline_v2/
DEPENDPATH += $(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Foundational/cmdline_v2/
DEPENDPATH += $(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Foundational/iwstring/
INCLUDEPATH +=$(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Foundational/iwstring/
DEPENDPATH += $(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Foundational/iwaray/
INCLUDEPATH +=$(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Foundational/iwaray/
DEPENDPATH += $(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Foundational/iwcrex/
INCLUDEPATH +=$(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Foundational/iwcrex/
DEPENDPATH += $(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Foundational/iwbits/
INCLUDEPATH +=$(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Foundational/iwbits/
DEPENDPATH += $(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Foundational/mtrand/
INCLUDEPATH +=$(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Foundational/mtrand/
DEPENDPATH += $(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Foundational/data_source/
INCLUDEPATH +=$(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Foundational/data_source/
DEPENDPATH += $(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Foundational/accumulator/
INCLUDEPATH +=$(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Foundational/accumulator/
DEPENDPATH += $(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Foundational/iwqsort/
INCLUDEPATH +=$(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Foundational/iwqsort/
DEPENDPATH += $(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Foundational/xmlParser/
INCLUDEPATH +=$(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Foundational/xmlParser/
DEPENDPATH += $(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Foundational/include/
INCLUDEPATH +=$(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Foundational/include/
DEFINES+= IW_IMPLEMENTATIONS_EXPOSED=1

DEFINES +="PROGRAM_VERSION=\"\\\"$$system(svnversion -n)\\\"\""

INCLUDEPATH += $(TG_DIR)/BACKEND/SRC/BioRels/libs/common/
INCLUDEPATH += $(TG_DIR)/BACKEND/SRC/BioRels/libs/common/headers/
INCLUDEPATH += $(TG_DIR)/BACKEND/SRC/BioRels/libs/common/sources/

CONFIG(release, debug|release):PRE_TARGETDEPS +=  $(TG_DIR)/BACKEND/SRC/BioRels/build/release/libs/common/libprotint.a
CONFIG(debug, debug|release):  PRE_TARGETDEPS += $(TG_DIR)/BACKEND/SRC/BioRels/build/debug/libs/common/libprotint.a

CONFIG(release, debug|release):LIBS += -L$(TG_DIR)/BACKEND/SRC/BioRels/build/release/libs/common/ -lprotint
CONFIG(debug, debug|release):LIBS += -L$(TG_DIR)/BACKEND/SRC/BioRels/build/debug/libs/common/ -lprotint


SOURCES += $(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Molecule_Lib/aromatic.cc \
    $(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Molecule_Lib/atom.cc \
    $(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Molecule_Lib/atom_alias.cc \
    $(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Molecule_Lib/atom_typing.cc \
    $(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Molecule_Lib/bond.cc \
    $(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Molecule_Lib/bond_list.cc \
    $(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Molecule_Lib/careful_frag.cc \
    $(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Molecule_Lib/charge_assigner.cc \
    $(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Molecule_Lib/charge_calculation.cc \
    $(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Molecule_Lib/charmm.cc \
    $(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Molecule_Lib/chiral_centre.cc \
    $(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Molecule_Lib/cif.cc \
    $(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Molecule_Lib/cis_trans_bond.cc \
    $(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Molecule_Lib/coordinates.cc \
    $(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Molecule_Lib/coordinates_double.cc \
    $(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Molecule_Lib/csubstructure.cc \
    $(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Molecule_Lib/dihedral.cc \
    $(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Molecule_Lib/donor_acceptor.cc \
    $(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Molecule_Lib/element.cc \
    $(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Molecule_Lib/element_hits_needed.cc \
    $(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Molecule_Lib/ematch.cc \
    $(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Molecule_Lib/etrans.cc \
    $(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Molecule_Lib/frag.cc \
    $(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Molecule_Lib/inchi_dummy.cc \
    $(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Molecule_Lib/is_actually_chiral.cc \
    $(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Molecule_Lib/ISIS_Atom_List.cc \
    $(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Molecule_Lib/isis_link_atom.cc \
    $(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Molecule_Lib/_istream_and_type.cc \
    $(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Molecule_Tools/iwmfingerprint.cc \
    $(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Molecule_Lib/iwqry_wstats.cc \
    $(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Molecule_Lib/iwrcb.cc \
    $(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Molecule_Lib/iwrnm.cc \
    $(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Molecule_Lib/iwsubstructure.cc \
  #  $(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Molecule_Lib/maccskeys_fn5.cc \
    $(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Molecule_Lib/marvin.cc \
    $(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Molecule_Lib/mdl.cc \
    $(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Molecule_Lib/mdl_atom_record.cc \
    $(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Molecule_Lib/mdl_file_data.cc \
    $(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Molecule_Lib/mdl_molecule.cc \
    $(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Molecule_Lib/mdl_v30.cc \
    $(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Molecule_Lib/misc2.cc \
    $(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Molecule_Lib/mmod.cc \
    $(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Molecule_Lib/molecule.cc \
    $(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Molecule_Lib/moleculeb.cc \
    $(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Molecule_Lib/moleculed.cc \
    $(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Molecule_Lib/moleculeh.cc \
    $(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Molecule_Lib/moleculer.cc \
    $(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Molecule_Lib/molecule_smarts.cc \
    $(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Molecule_Lib/molecule_to_query.cc \
    $(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Molecule_Lib/mpr.cc \
    $(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Molecule_Lib/mrk.cc \
    $(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Molecule_Lib/msi.cc \
    $(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Molecule_Lib/ostream_and_type.cc \
    $(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Molecule_Lib/output.cc \
    $(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Molecule_Lib/parse_smarts_tmp.cc \
    $(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Molecule_Lib/path.cc \
    $(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Molecule_Lib/path_scoring.cc \
    $(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Molecule_Lib/pdb.cc \
    $(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Molecule_Lib/pearlman.cc \
    $(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Molecule_Lib/ring_bond_iterator.cc \
    $(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Molecule_Lib/rmele.cc \
    $(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Molecule_Lib/rwmolecule.cc \
    $(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Molecule_Lib/rwsubstructure.cc \
    $(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Molecule_Lib/set_of_atoms.cc \
    $(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Molecule_Lib/smi.cc \
    $(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Molecule_Lib/smiles.cc \
    $(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Molecule_Lib/smiles_support.cc \
    $(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Molecule_Lib/ss_atom_env.cc \
    $(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Molecule_Lib/ss_bonds.cc \
    $(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Molecule_Lib/ss_ring.cc \
    $(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Molecule_Lib/ss_ring_base.cc \
    $(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Molecule_Lib/ss_ring_sys.cc \
    $(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Molecule_Lib/standardise.cc \
    $(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Molecule_Lib/substructure_a.cc \
    $(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Molecule_Lib/substructure_chiral.cc \
    $(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Molecule_Lib/substructure_env.cc \
    $(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Molecule_Lib/substructure_nmab.cc \
    $(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Molecule_Lib/substructure_results.cc \
    $(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Molecule_Lib/substructure_spec.cc \
    $(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Molecule_Lib/symm_class_can_rank.cc \
    $(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Molecule_Lib/symmetry.cc \
    $(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Molecule_Lib/target.cc \
    $(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Molecule_Lib/temp_detach_atoms.cc \
    $(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Molecule_Lib/toggle_kekule_form.cc \
    $(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Molecule_Lib/tokenise_atomic_smarts.cc \
    $(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Molecule_Lib/tripos.cc \
    $(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Molecule_Lib/unique.cc \
    $(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Molecule_Lib/van_der_waals.cc \
    $(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Foundational/cmdline/cmdline.cc \
    $(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Foundational/iwmisc/dash_f.cc \
    $(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Foundational/iwbits/du_bin2ascii.cc \
    $(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Foundational/iwmisc/fraction_as_string.cc \
    $(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Foundational/iwmisc/int_comparator.cc \
    $(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Foundational/iwbits/iwbits.cc \
    $(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Foundational/iwmisc/iwdigits.cc \
    $(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Foundational/iwcrex/iwgrep-2.5.cc \
    $(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Foundational/iwstring/iwstring_and_file_descriptor.cc \
    $(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Foundational/iwstring/IWString_class.cc \
    $(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Foundational/data_source/iwstring_data_source.cc \
    $(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Foundational/iwstring/iwstrncasecmp.cc \
    $(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Foundational/iw_tdt/iw_tdt.cc \
    $(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Foundational/iwstring/iwwrite.cc \
    $(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Foundational/iwstring/iwwrite_block.cc \
    $(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Foundational/iwstring/iwzlib.cc \
    $(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Foundational/accumulator/KahanSum.cc \
    $(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Foundational/iwmisc/logical_expression.cc \
    $(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Foundational/iwmisc/msi_object.cc \
    $(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Foundational/mtrand/mtrand.cc \
    $(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Foundational/iwmisc/new_int.cc \
    $(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Foundational/data_source/string_data_source.cc \
    $(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Foundational/iwmisc/write_space_suppressed_string.cc \
    $(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Foundational/xmlParser/xmlParser.cpp \
    $(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Foundational/iwbits/bits_in_common.c \
    $(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Foundational/iwcrex/grep-2.5.regex.c \
    $(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Foundational/mtrand/iwrandom.cc \
    $(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Foundational/mtrand/normal_distribution.cc \
    $(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Foundational/mtrand/_random_number_between_double.cc \
    $(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Foundational/mtrand/_random_number_between_float.cc \
    $(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Foundational/mtrand/_random_number_between_int.cc \
    $(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Foundational/mtrand/_random_number_between_long.cc \
    $(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Foundational/mtrand/_random_number_between_long_long.cc \
    $(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Foundational/mtrand/_random_number_between_uint.cc \
    $(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Foundational/mtrand/_random_number_between_unsigned_long.cc \
    $(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Foundational/iwstring/iwstring.cc \
    $(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Foundational/iwstring/iw_stl_hash_map.cc \
    $(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Foundational/iwmisc/sparse_fp_creator.cc \
     $(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Foundational/cmdline/getopt.cc \ #ADDED
    $(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Foundational/iwcrex/_iwgrep25regex.cc \
    $(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Foundational/iwmisc/sparse_fp_creator_support.cc \
    $(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Foundational/iwmisc/endian.cc \
    $(TG_DIR)/BACKEND/SRC/BioRels/libs/iwlib2/sources/iwcore/iwicf.cpp \
    $(TG_DIR)/BACKEND/SRC/BioRels/libs/iwlib2/sources/iwcore/macrotoiw.cpp \
    $(TG_DIR)/BACKEND/SRC/BioRels/libs/iwlib2/sources/iwcore/substrSearch.cpp \
    $(TG_DIR)/BACKEND/SRC/BioRels/libs/iwlib2/sources/proc/dockmatch/compclique.cpp \
    $(TG_DIR)/BACKEND/SRC/BioRels/libs/iwlib2/sources/proc/dockmatch/dockmatch.cpp \
    $(TG_DIR)/BACKEND/SRC/BioRels/libs/iwlib2/sources/proc/dockmatch/entrymatch.cpp \
    $(TG_DIR)/BACKEND/SRC/BioRels/libs/iwlib2/sources/statics/prepMole.cpp

HEADERS += \
     $(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Foundational/accumulator/accumulator.h \
$(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Molecule_Lib/aromatic.h \
$(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Molecule_Lib/atom.h \
$(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Molecule_Lib/atom_typing.h \
$(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Molecule_Lib/bond.h \
$(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Molecule_Lib/bond_list.h \
$(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Molecule_Lib/charge_assigner.h \
$(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Molecule_Lib/chiral_centre.h \
$(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Foundational/cmdline/cmdline.h \
$(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Foundational/cmdline_v2/cmdline_v2.h \
$(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Molecule_Lib/collection_template.h \
$(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Molecule_Lib/coordinates.h \
$(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Molecule_Lib/dihedral.h \
$(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Molecule_Lib/donor_acceptor.h \
$(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Molecule_Lib/element.h \
$(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Molecule_Lib/ematch.h \
$(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Molecule_Lib/etrans.h \
$(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Molecule_Lib/is_actually_chiral.h \
$(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Molecule_Lib/istream_and_type.h \
$(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Molecule_Lib/iwaray.h \
$(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Molecule_Lib/iwarchive.h \
$(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Molecule_Lib/iw_auto_array.h \
$(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Molecule_Lib/iwbits.h \
$(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Molecule_Lib/iwconfig.h \
$(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Molecule_Lib/iwcrex.h \
$(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Molecule_Lib/iwdigits.h \
$(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Molecule_Lib/iwhash.h \
$(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Molecule_Lib/iwmfingerprint.h \
$(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Molecule_Lib/iwminmax.h \
$(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Molecule_Lib/iwmtypes.h \
$(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Molecule_Lib/iwqsort.h \
$(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Molecule_Lib/iwrandom.h \
$(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Molecule_Lib/iwstandard.h \
$(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Molecule_Lib/iw_stl_hash_map.h \
$(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Molecule_Lib/iw_stl_hash_multimap.h \
$(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Molecule_Lib/iw_stl_hash_multiset.h \
$(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Molecule_Lib/iw_stl_hash_set.h \
$(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Molecule_Lib/iwstring.h \
$(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Molecule_Lib/iwstring_data_source.h \
$(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Molecule_Lib/iw_tdt.h \
$(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Molecule_Lib/iw_vdw.h \
$(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Molecule_Lib/iwzlib.h \
$(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Molecule_Lib/kahan_sum.h \
$(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Molecule_Lib/logical_expression.h \
#$(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Molecule_Lib/maccskeys_fn5.h \
$(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Molecule_Lib/minmaxspc.h \
$(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Molecule_Lib/misc.h \
$(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Molecule_Lib/molecule.h \
$(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Molecule_Lib/molecule_to_query.h \
$(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Molecule_Lib/msi_object.h \
$(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Molecule_Lib/mtrand.h \
$(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Molecule_Lib/ostream_and_type.h \
$(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Molecule_Lib/output.h \
$(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Molecule_Lib/path.h \
$(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Molecule_Lib/path_scoring.h \
$(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Molecule_Lib/primes.h \
$(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Molecule_Lib/qry_wstats.h \
$(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Molecule_Lib/report_progress.h \
$(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Molecule_Lib/rmele.h \
$(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Molecule_Lib/rwmolecule.h \
$(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Molecule_Lib/set_of_atoms.h \
$(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Molecule_Lib/set_or_unset.h \
$(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Molecule_Lib/smiles.h \
$(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Molecule_Lib/space_vector.h \
$(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Molecule_Lib/string_data_source.h \
$(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Molecule_Lib/substructure.h \
$(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Molecule_Lib/target.h \
$(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Molecule_Lib/temp_detach_atoms.h \
$(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Molecule_Lib/toggle_kekule_form.h \
$(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Molecule_Lib/xmlParser.h \
    $(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Molecule_Lib/atom_alias.h \
    $(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Molecule_Lib/careful_frag.h \
    $(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Molecule_Lib/charge_calculation.h \
    $(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Molecule_Lib/iwrcb.h \
    $(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Molecule_Lib/iwrnm.h \
    $(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Molecule_Lib/jibomolecule.h \
    $(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Molecule_Lib/marvin.h \
    $(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Molecule_Lib/mdl.h \
    $(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Molecule_Lib/mdl_atom_record.h \
    $(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Molecule_Lib/mdl_file_data.h \
    $(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Molecule_Lib/mdl_molecule.h \
    $(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Molecule_Lib/misc2.h \
    $(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Molecule_Lib/molecule_arom.h \
    $(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Molecule_Lib/molecule_cif.h \
    $(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Molecule_Lib/molecule_ctb.h \
    $(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Molecule_Lib/moleculed.h \
    $(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Molecule_Lib/moleculeh.h \
    $(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Molecule_Lib/molecule_main.h \
    $(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Molecule_Lib/molecule_marvin.h \
    $(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Molecule_Lib/molecule_mdl.h \
    $(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Molecule_Lib/molecule_smarts.h \
    $(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Molecule_Lib/molecule_smi.h \
    $(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Molecule_Lib/molecule_tripos.h \
    $(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Molecule_Lib/mpr.h \
    $(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Molecule_Lib/parse_smarts_tmp.h \
    $(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Molecule_Lib/pearlman.h \
    $(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Molecule_Lib/readmdl.h \
    $(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Molecule_Lib/rwsubstructure.h \
    $(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Molecule_Lib/symmetry.h \
    $(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Molecule_Lib/tmpsssr.h \
    $(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Molecule_Lib/tokenise_atomic_smarts.h \
    $(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Molecule_Lib/tripos.h \
    $(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Foundational/iwbits/dy_fingerprint.h \
    $(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Foundational/iwcrex/grep-2.5.config.h \
    $(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Foundational/iwcrex/grep-2.5.posix.regex.h \
    $(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Foundational/iwcrex/grep-2.5.regex.h \
    $(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Foundational/iwbits/iwbits_support.h \
    $(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Foundational/iwcrex/iwgrep-2.5.h \
    $(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Foundational/iwmisc/new_array_.h \
    $(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Foundational/iwbits/precompbit.h \
    $(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Foundational/mtrand/mtrand.h \
    $(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Foundational/iwstring/iwhash.h \
    $(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Foundational/iwstring/iw_stl_hash_map.h \
   $(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Foundational/iwmisc/sparse_fp_creator.h \
    $(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Foundational/cmdline/getopt.h \ #ADDED
    $(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Foundational/include/iwconfig.h \
    $(TG_DIR)/BACKEND/SRC/LillyMol-master/src/Foundational/iwcrex/iwcrex.h \
    $(TG_DIR)/BACKEND/SRC/BioRels/libs/iwlib2/headers/iwcore/iwicf.h \
    $(TG_DIR)/BACKEND/SRC/BioRels/libs/iwlib2/headers/iwcore/macrotoiw.h \
    $(TG_DIR)/BACKEND/SRC/BioRels/libs/iwlib2/headers/iwcore/substrSearch.h \
    $(TG_DIR)/BACKEND/SRC/BioRels/libs/iwlib2/headers/proc/dockmatch/compclique.h \
    $(TG_DIR)/BACKEND/SRC/BioRels/libs/iwlib2/headers/proc/dockmatch/dockmatch.h \
    $(TG_DIR)/BACKEND/SRC/BioRels/libs/iwlib2/headers/proc/dockmatch/entrymatch.h \
    $(TG_DIR)/BACKEND/SRC/BioRels/libs/iwlib2/headers/statics/prepMole.h

