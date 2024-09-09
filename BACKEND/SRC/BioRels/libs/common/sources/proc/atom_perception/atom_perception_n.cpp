#include "headers/proc/atomperception.h"
#include "headers/statics/intertypes.h"
#include "headers/molecule/mmatom.h"
#include "headers/molecule/mmbond.h"
#include "headers/molecule/macromole.h"
#include "headers/molecule/mmatom_utils.h"
#include "headers/statics/logger.h"
#undef NDEBUG /// Active assertion in release
const std::vector<protspace::AtomRule> protspace::AtomPerception::mNitrogenRules={
    {7,{{6,BOND::SINGLE},{6,BOND::DOUBLE}},"N.ar",true,true,0}, ///001:N23//13673
    {7,{{6,BOND::SINGLE}},"N.3",false,false,0}, ///004:N//7133
    {7,{{6,BOND::SINGLE},{6,BOND::SINGLE}},"N.3",false,false,0}, ///009:N2//5435
    {7,{{6,BOND::DOUBLE},{7,BOND::SINGLE}},"N.ar",true,true,0}, ///003:N16//2359
    {7,{{6,BOND::SINGLE},{6,BOND::SINGLE},{1,BOND::SINGLE}},"N.3",false,false,0}, ///009:N2//1603
    {7,{{6,BOND::TRIPLE}},"N.1",false,false,0},/// 03U:N24//682
    {7,{{6,BOND::SINGLE},{7,BOND::SINGLE},{1,BOND::SINGLE}},"N.3",false,false,0}, ///01H:N6//638
    {7,{{6,BOND::SINGLE},{7,BOND::DOUBLE}},"N.2",false,false,0},/// 04U:N26//526
    {7,{{6,BOND::DOUBLE},{8,BOND::SINGLE}},"N.ar",true,true,0},/// 02J:N2//508
    {7,{{7,BOND::SINGLE},{7,BOND::DOUBLE}},"N.ar",true,true,1},/// 038:N6/N5//437
    {7,{{6,BOND::DOUBLE}},"N.2",false,false,0},/// 0AR:NH1//282
    {7,{{6,BOND::DOUBLE},{6,BOND::SINGLE},{6,BOND::SINGLE}},"N.ar",true,true,1},/// 02P:N21//277
    {7,{{6,BOND::SINGLE},{7,BOND::SINGLE}},"N.3",false,false,0}, ///01H:N6// 178
    {7,{{6,BOND::SINGLE},{6,BOND::DOUBLE},{1,BOND::SINGLE}},"N.pl3",true,true,1},/// 0EK:ND1//84
    {7,{{6,BOND::SINGLE},{8,BOND::SINGLE}},"N.3",false,false,0},/// 0SK:NBE//72
    {7,{{7,BOND::DOUBLE}},"N.2",false,false,0},/// 04U:N28//71
    {7,{{6,BOND::SINGLE},{15,BOND::SINGLE}},"N.3",false,false,0},/// 08Q:N4//65
    {7,{{7,BOND::SINGLE}},"N.3",false,false,0}, ///01H:N7//49
    {7,{{15,BOND::SINGLE},{15,BOND::SINGLE}},"N.3",false,false,0},/// 0G4:N3B//30
    {7,{{6,BOND::DOUBLE},{6,BOND::SINGLE},{26,BOND::SINGLE}},"N.pl3",true,true,1},/// 2FH:NA/BD//28
    {7,{{7,BOND::DOUBLE},{16,BOND::SINGLE}},"N.ar",true,true,0},/// 0DQ:N29//17
    {7,{{7,BOND::SINGLE},{7,BOND::SINGLE},{1,BOND::SINGLE}},"N.pl3",true,true,0},/// 100:N1//14
    {7,{{15,BOND::SINGLE}},"N.3",false,false,0},/// 2PA:N4/N5//13
    {7,{{6,BOND::SINGLE},{8,BOND::DOUBLE}},"N.3",false,false,0},/// 2RK:N19//9
    {7,{{7,BOND::TRIPLE}},"N.1",false,false,0},/// 02Y:N2//8
    {7,{{7,BOND::SINGLE},{8,BOND::DOUBLE}},"N.2",false,false,0},/// 0QA:N3//5
    {7,{{6,BOND::DOUBLE},{1,BOND::SINGLE},{1,BOND::SINGLE}},"N.2",false,false,1},/// 3AR:NH2//5
    {7,{{6,BOND::SINGLE},{6,BOND::DOUBLE},{44,BOND::SINGLE}},"N.pl3",true,true,1},/// AG1:N21//2
    {7,{{6,BOND::SINGLE},{7,BOND::TRIPLE}},"N.1",false,false,1},/// 02Y:N2//2
    {7,{{6,BOND::SINGLE},{6,BOND::TRIPLE}},"N.1",false,false,0},/// 6CU:NAN//2
    {7,{{5,BOND::SINGLE},{6,BOND::SINGLE},{6,BOND::SINGLE}},"N.3",false,false,0},/// A48:N//1
    {7,{{6,BOND::SINGLE},{6,BOND::SINGLE},{26,BOND::SINGLE}},"N.pl3",true,true,0},/// 2FH:NC/NB//0
    {7,{{6,BOND::DOUBLE},{15,BOND::SINGLE}},"N.2",true,false,0},/// 0NQ:N5//1
    {7,{{6,BOND::SINGLE},{6,BOND::SINGLE},{27,BOND::SINGLE}},"N.3",true,false,0},/// B13:N21/N23/N22/N24//0
    {7,{{6,BOND::SINGLE},{6,BOND::SINGLE},{29,BOND::SINGLE}},"N.3",true,false,0},/// 0TE:N1//0
    {7,{{6,BOND::SINGLE},{7,BOND::SINGLE},{7,BOND::SINGLE}},"N.pl3",true,true,0},/// 3EJ:N11//0
    {7,{{5,BOND::SINGLE},{6,BOND::SINGLE},{7,BOND::SINGLE}},"N.3",true,false,0},/// 45J:N//0
    {7,{{6,BOND::SINGLE},{6,BOND::SINGLE},{12,BOND::SINGLE}},"N.pl3",true,false,0},/// 68G:NA/NB//0
    {7,{{5,BOND::SINGLE},{6,BOND::SINGLE}},"N.3",true,false,0},/// 6OQ:N04//1
    {7,{{6,BOND::SINGLE},{7,BOND::SINGLE},{29,BOND::SINGLE}},"N.3",true,false,0},/// 0TE:N3//0
    {7,{{6,BOND::SINGLE},{6,BOND::SINGLE},{6,BOND::SINGLE},{1,BOND::SINGLE}},"N.4",true,false,1},/// 0FY:N19//0
    {7,{{6,BOND::SINGLE},{6,BOND::SINGLE},{44,BOND::SINGLE}},"N.3",true,false,0},/// 11R:NCL/NCA/NAA/NBA//0
    {7,{{8,BOND::SINGLE},{1,BOND::SINGLE},{1,BOND::SINGLE}},"N.3",false,false,0},/// 127:ND'//0
    {7,{{6,BOND::DOUBLE},{16,BOND::SINGLE}},"N.ar",true,true,0},/// 0FY:N16//0
    {7,{{8,BOND::SINGLE}},"N.3",false,false,0},/// 127:ND'
    {7,{{6,BOND::AROMATIC_BD},{6,BOND::AROMATIC_BD}},"N.ar",true,true,0}, ///001:N23//0
    {7,{{6,BOND::SINGLE},{6,BOND::SINGLE},{7,BOND::SINGLE}},"N.pl3",true,true,0}, ///004:N15//0
    {7,{{6,BOND::AROMATIC_BD},{6,BOND::SINGLE},{7,BOND::AROMATIC_BD}},"N.pl3",true,true,0}, ///004:N15//0
    {7,{{6,BOND::SINGLE},{1,BOND::SINGLE},{1,BOND::SINGLE}},"N.3",false,false,0}, ///004:N//0
    {7,{{6,BOND::SINGLE},{6,BOND::SINGLE},{6,BOND::SINGLE}},"N.3",false,false,0}, ///009:N27//0
    {7,{{6,BOND::SINGLE},{6,BOND::SINGLE}},"N.3",false,false,0}, ///009:N2//0
    {7,{{7,BOND::SINGLE},{1,BOND::SINGLE},{1,BOND::SINGLE}},"N.3",false,false,0}, ///01H:N7
    {7,{{6,BOND::SINGLE},{1,BOND::SINGLE},{1,BOND::SINGLE},{1,BOND::SINGLE}},"N.4",false,false,1}, ///01W:N
    {7,{{6,BOND::SINGLE},{6,BOND::SINGLE},{8,BOND::SINGLE}},"N.3",false,false,0},/// 023:N7
    {7,{{6,BOND::AROMATIC_BD},{6,BOND::AROMATIC_BD},{6,BOND::SINGLE}},"N.ar",true,true,1},/// 02P:N21
    {7,{{7,BOND::DOUBLE},{7,BOND::DOUBLE}},"N.2",false,false,1},/// 04U:N27//71
    {7,{{6,BOND::SINGLE},{7,BOND::SINGLE},{8,BOND::SINGLE}},"N.pl3",true,true,0},/// 1N4:NAN
    {7,{{6,BOND::AROMATIC_BD},{7,BOND::AROMATIC_BD},{8,BOND::SINGLE}},"N.pl3",true,true,0},/// 1N4:NAN
    {7,{{6,BOND::DOUBLE},{7,BOND::SINGLE},{1,BOND::SINGLE}},"N.pl3",true,true,1},/// 3DE:N13//0
    {7,{{6,BOND::SINGLE},{6,BOND::DOUBLE},{7,BOND::SINGLE}},"N.pl3",true,true,1},/// 3P4:N2//0
    {7,{{7,BOND::SINGLE},{7,BOND::SINGLE}},"N.3",true,false,0},/// 53A:N3//0
    {7,{{7,BOND::SINGLE},{7,BOND::TRIPLE}},"N.1",false,false,1},/// A1L:N8//0
    {7,{{6,BOND::SINGLE},{6,BOND::DOUBLE},{23,BOND::SINGLE}},"N.pl3",true,true,1},/// AIV:N12
    {7,{{6,BOND::SINGLE},{7,BOND::SINGLE},{27,BOND::SINGLE}},"N.3",true,false,0},/// B13:N21/N23/N22/N24
    {7,{{6,BOND::SINGLE},{6,BOND::DOUBLE},{27,BOND::SINGLE}},"N.pl3",true,true,1},/// B1M:N//16
    {7,{{6,BOND::SINGLE},{6,BOND::DOUBLE},{12,BOND::SINGLE}},"N.pl3",true,true,1},/// BCB:NA
    {7,{{16,BOND::SINGLE}},"N.3",false,false,0},/// BSC:NAA
    {7,{{1,BOND::SINGLE},{1,BOND::SINGLE},{16,BOND::SINGLE}},"N.3",false,false,0},/// BSC:NAA
    {7,{{5,BOND::SINGLE},{6,BOND::DOUBLE},{6,BOND::SINGLE}},"N.pl3",true,true,1},/// C08:N02
    {7,{{16,BOND::DOUBLE}},"N.2",false,false,0},/// CPM:N1/N2
    {7,{{6,BOND::SINGLE},{78,BOND::SINGLE}},"N.3",true,false,0},/// CX3:N1/N3
    {7,{{30,BOND::SINGLE}},"N.3",false,false,0},/// DAZ:N1/N2
    {7,{{7,BOND::DOUBLE},{26,BOND::SINGLE}},"N.2",false,false,0},/// FEA:NA
    {7,{{6,BOND::SINGLE},{6,BOND::DOUBLE},{31,BOND::SINGLE}},"N.pl3",true,true,1},/// GIX:NA/NB/NC/ND
    {7,{{8,BOND::DOUBLE},{16,BOND::SINGLE}},"N.2",false,false,0},/// GSN:NAN
    {7,{{6,BOND::SINGLE},{6,BOND::DOUBLE},{25,BOND::SINGLE}},"N.pl3",true,true,1},/// HNN:NA/NB/NC/ND
    {7,{{16,BOND::SINGLE},{16,BOND::SINGLE}},"N.3",false,false,0},/// K1R:NAF
    {7,{{6,BOND::SINGLE},{16,BOND::SINGLE}},"N.ar",true,true,0},/// LTI:N2
    {7,{{7,BOND::TRIPLE},{8,BOND::SINGLE}},"N.1",false,false,1}, ///N2O:N2
    {7,{{78,BOND::SINGLE}},"N.3",false,false,0}, ///NCP:N1/N2
    {7,{{1,BOND::SINGLE}},"N.4",false,false,1}, ///NH:N
    {7,{{1,BOND::SINGLE},{1,BOND::SINGLE}},"N.4",false,false,1}, ///NH2
    {7,{{8,BOND::DOUBLE}},"N.2",false,false,0}, ///NMO:N
    {7,{{76,BOND::SINGLE}},"N.3",false,false,0}, ///OHX:N
    {7,{{6,BOND::SINGLE},{7,BOND::DOUBLE},{7,BOND::SINGLE}},"N.pl3",true,true,1},/// O16:N3
    {7,{{6,BOND::SINGLE},{44,BOND::SINGLE}},"N.pl3",true,false,0}, ///OSV:NAM
    {7,{{15,BOND::DOUBLE}},"N.2",false,false,0}, ///PON:N7
    {7,{{6,BOND::SINGLE},{6,BOND::DOUBLE},{78,BOND::SINGLE}},"N.pl3",true,true,1},/// PT2:N10/N1
    {7,{{6,BOND::DOUBLE},{45,BOND::SINGLE}},"N.2",false,false,0},/// R1C:N5/N6
    {7,{{6,BOND::SINGLE},{6,BOND::DOUBLE},{45,BOND::SINGLE}},"N.pl3",true,true,1},/// R1C:N5/N6
    {7,{{6,BOND::SINGLE},{45,BOND::SINGLE}},"N.3",false,false,0},/// RHM:N12
    {7,{{17,BOND::SINGLE}},"N.3",false,false,0},/// TKL:NZ
    {7,{{7,BOND::SINGLE},{16,BOND::DOUBLE}},"N.2",true,false,0},/// V21:N14
    {7,{{7,BOND::SINGLE},{8,BOND::SINGLE}},"N.3",false,false,0},/// YX1:NAO
    {7,{{7,BOND::AROMATIC_BD},{6,BOND::AROMATIC_BD}},"N.ar",true,true,0},///003:N16
    {7,{{1,BOND::SINGLE},{6,BOND::AROMATIC_BD},{6,BOND::AROMATIC_BD}},"N.pl3",true,true,0},///00G:N7
    {7,{{1,BOND::SINGLE},{6,BOND::AROMATIC_BD},{7,BOND::AROMATIC_BD}},"N.pl3",true,true,0},///027:N10
    {7,{{6,BOND::AROMATIC_BD},{8,BOND::AROMATIC_BD}},"N.ar",true,true,0},///02J:N2
    {7,{{7,BOND::AROMATIC_BD},{7,BOND::AROMATIC_BD}},"N.ar",true,true,0},///038:N6
    {7,{{6,BOND::AROMATIC_BD},{6,BOND::AROMATIC_BD},{7,BOND::AROMATIC_BD}},"N.ar",true,true,0},///052:N4
    {7,{{6,BOND::AROMATIC_BD},{6,BOND::AROMATIC_BD},{7,BOND::SINGLE}},"N.pl3",true,true,0},///08W:N16
    {7,{{6,BOND::AROMATIC_BD},{6,BOND::AROMATIC_BD},{8,BOND::SINGLE}},"N.pl3",true,true,0},///09M:N3
    {7,{{7,BOND::AROMATIC_BD},{16,BOND::AROMATIC_BD}},"N.ar",true,true,0},///0DQ:N29
    {7,{{6,BOND::AROMATIC_BD},{16,BOND::AROMATIC_BD}},"N.ar",true,true,0},///0FY:N16
    {7,{{6,BOND::SINGLE},{6,BOND::SINGLE},{6,BOND::SINGLE},{6,BOND::SINGLE}},"N.4",true,false,1},///0HK:N2
    {7,{{6,BOND::AROMATIC_BD},{6,BOND::AROMATIC_BD},{6,BOND::AROMATIC_BD}},"N.ar",true,true,0},///0T6:N3
    {7,{{6,BOND::AROMATIC_BD},{8,BOND::AROMATIC_BD}},"N.ar",true,true,0},///0X3:NAJ/NAC
    {7,{{1,BOND::SINGLE},{7,BOND::AROMATIC_BD},{7,BOND::AROMATIC_BD}},"N.pl3",true,true,0},///100:N1
    {7,{{6,BOND::AROMATIC_BD},{6,BOND::AROMATIC_BD},{26,BOND::SINGLE}},"N.pl3",true,true,1},/// 2FH:NA/BD//28
    {7,{{6,BOND::AROMATIC_BD},{6,BOND::AROMATIC_BD},{29,BOND::SINGLE}},"N.pl3",true,true,0},/// 35N:NA/NB/NC/ND
    {7,{{6,BOND::SINGLE},{6,BOND::SINGLE},{1,BOND::SINGLE},{1,BOND::SINGLE}},"N.4",true,false,1},///3XR:NAI
    {7,{{6,BOND::AROMATIC_BD},{6,BOND::AROMATIC_BD},{6,BOND::AMIDE}},"N.pl3",true,true,0},/// 3Y6:NAJ
    {7,{{6,BOND::AROMATIC_BD},{7,BOND::AROMATIC_BD},{7,BOND::AROMATIC_BD}},"N.ar",true,true,0},/// 5BP:N9
    {7,{{6,BOND::AROMATIC_BD},{7,BOND::AROMATIC_BD},{6,BOND::AMIDE}},"N.pl3",true,true,0},/// 63L:N5
    {7,{{6,BOND::AROMATIC_BD},{6,BOND::AROMATIC_BD},{23,BOND::SINGLE}},"N.pl3",true,true,1},/// AIV:N12
    {7,{{6,BOND::SINGLE},{6,BOND::SINGLE},{78,BOND::SINGLE}},"N.3",true,false,0},/// CX8:N2
    {7,{{6,BOND::SINGLE},{6,BOND::SINGLE},{6,BOND::SINGLE},{8,BOND::SINGLE}},"N.4",true,false,1},/// DP4:N1
    {7,{{6,BOND::SINGLE},{6,BOND::SINGLE},{30,BOND::SINGLE}},"N.3",true,false,0},/// HE5:NC
    {7,{{6,BOND::SINGLE},{6,BOND::SINGLE},{15,BOND::SINGLE}},"N.3",true,false,0},/// HIP:ND1
    {7,{{6,BOND::SINGLE},{6,BOND::SINGLE},{28,BOND::SINGLE}},"N.pl3",true,false,0},/// MM6:N4
    {7,{{6,BOND::SINGLE},{6,BOND::SINGLE},{45,BOND::SINGLE}},"N.3",true,false,0},/// RHM:N8
    {7,{{6,BOND::SINGLE},{6,BOND::SINGLE},{7,BOND::DOUBLE}},"N.ar",true,true,0},
    {7,{{1,BOND::SINGLE},{1,BOND::SINGLE},{7,BOND::SINGLE},{7,BOND::SINGLE}},"N.4",false,false,1},
    {7,{{1,BOND::SINGLE},{6,BOND::SINGLE},{7,BOND::SINGLE}},"N.3",false,false,0},
    {7,{{1,BOND::SINGLE},{6,BOND::SINGLE},{7,BOND::DOUBLE}},"N.pl3",false,false,1},
    {7,{{1,BOND::SINGLE},{6,BOND::DOUBLE}},"N.2",false,false,0},
    {7,{{1,BOND::SINGLE},{6,BOND::SINGLE}},"N.3",false,false,0},
    {7,{{6,BOND::SINGLE},{6,BOND::SINGLE},{6,BOND::AMIDE}},"N.pl3",true,false,0},///003:N1
    {7,{{6,BOND::SINGLE},{6,BOND::AMIDE},{7,BOND::SINGLE}},"N.pl3",true,false,0},///00P:N10
    {7,{{6,BOND::SINGLE},{6,BOND::AMIDE}},"N.pl3",true,false,0},///08W:N13
    {7,{{6,BOND::DOUBLE},{6,BOND::AMIDE}},"N.2",true,false,0},///0AP:N1
    {7,{{6,BOND::AMIDE},{7,BOND::SINGLE}},"N.pl3",true,false,0},///2W4:N03
    {7,{{6,BOND::SINGLE},{6,BOND::DOUBLE},{6,BOND::AMIDE}},"N.4",true,false,1},///3MC:N3
    {7,{{6,BOND::SINGLE},{6,BOND::AMIDE},{6,BOND::AMIDE}},"N.pl3",true,false,1},///C4B:N1
    {7,{{6,BOND::SINGLE},{6,BOND::SINGLE},{31,BOND::SINGLE}},"N.pl3",true,false,0},///GIX:NA/NC
    {7,{{6,BOND::SINGLE},{6,BOND::SINGLE},{25,BOND::SINGLE}},"N.pl3",true,false,0},///HNN:NB/ND
    {7,{{6,BOND::SINGLE},{6,BOND::AMIDE},{8,BOND::SINGLE}},"N.3",true,false,0},///QUS:N14
    {7,{{6,BOND::SINGLE},{6,BOND::SINGLE},{16,BOND::SINGLE}},"N.pl3",true,false,0},///SG2:N
    {7,{{6,BOND::DOUBLE},{44,BOND::SINGLE}},"N.2",false,false,0},///0H2:n23
    {7,{{6,BOND::SINGLE},{26,BOND::SINGLE},{6,BOND::SINGLE},{6,BOND::SINGLE}},"N.4",true,false,1},///188:N2
    {7,{{6,BOND::SINGLE},{6,BOND::DOUBLE},{30,BOND::SINGLE}},"N.4",true,true,1},///2GO:N
    {7,{{6,BOND::SINGLE},{6,BOND::DOUBLE},{49,BOND::SINGLE}},"N.4",true,true,1},///3ZZ:N
    {7,{{6,BOND::SINGLE},{6,BOND::SINGLE},{49,BOND::SINGLE}},"N.pl3",true,false,0},///3ZZ:N
    {7,{{6,BOND::SINGLE},{77,BOND::SINGLE}},"N.4",true,false,1},///4IR
    {7,{{27,BOND::SINGLE}},"N.3",false,false,0},///CON
    {7,{{77,BOND::SINGLE}},"N.3",false,false,0},///IRI
    {7,{{44,BOND::SINGLE}},"N.3",false,false,0},///NRU
    {7,{{45,BOND::SINGLE}},"N.3",false,false,0},///RHD
    {7,{{6,BOND::SINGLE},{6,BOND::SINGLE},{6,BOND::SINGLE},{78,BOND::SINGLE}},"N.4",true,false,1},///NXC
    {7,{{26,BOND::SINGLE},{26,BOND::SINGLE},{26,BOND::SINGLE},{26,BOND::SINGLE},{26,BOND::SINGLE},{26,BOND::SINGLE}},"N.4",true,false,3},///CFN
    {7,{{6,BOND::SINGLE},{6,BOND::DOUBLE},{29,BOND::SINGLE}},"N.4",true,false,1},///CUF:N
    {7,{{6,BOND::SINGLE},{6,BOND::SINGLE},{76,BOND::SINGLE}},"N.3",true,false,0},///DOS
    {7,{{6,BOND::DOUBLE},{6,BOND::SINGLE},{76,BOND::SINGLE}},"N.ar",true,false,1},///DWC
    {7,{{6,BOND::DOUBLE},{6,BOND::SINGLE},{28,BOND::SINGLE}},"N.ar",true,false,1},///F43
    {7,{{6,BOND::SINGLE},{75,BOND::SINGLE}},"N.3",true,false,0},///REJ
    {7,{{6,BOND::SINGLE},{6,BOND::SINGLE},{6,BOND::SINGLE},{29,BOND::SINGLE}},"N.4",true,false,1},///MM2
    {7,{{6,BOND::SINGLE},{6,BOND::SINGLE},{6,BOND::SINGLE},{28,BOND::SINGLE}},"N.4",true,false,1},///MM5
    {7,{{6,BOND::SINGLE},{6,BOND::SINGLE},{6,BOND::SINGLE},{39,BOND::SINGLE}},"N.4",true,false,1},///YBT
    {7,{{1,BOND::SINGLE},{1,BOND::SINGLE},{6,BOND::SINGLE},{44,BOND::SINGLE}},"N.4",true,false,1},///HRU
    {7,{{1,BOND::SINGLE},{1,BOND::SINGLE},{6,BOND::SINGLE},{78,BOND::SINGLE}},"N.4",true,false,1},///0JC
    {7,{{1,BOND::SINGLE},{1,BOND::SINGLE},{6,BOND::SINGLE},{78,BOND::SINGLE}},"N.4",true,true,1},///2Pt
    {7,{{6,BOND::SINGLE},{6,BOND::DOUBLE},{24,BOND::SINGLE}},"N.2",true,false,1},///TIL
    {7,{{1,BOND::SINGLE},{6,BOND::SINGLE},{78,BOND::SINGLE}},"N.3",false,false,0},///61C
    {7,{{1,BOND::SINGLE},{1,BOND::SINGLE},{78,BOND::SINGLE}},"N.3",false,false,0},///61C
    {7,{{1,BOND::SINGLE},{6,BOND::SINGLE},{15,BOND::SINGLE}},"N.3",false,false,0},///6MG
    {7,{{1,BOND::SINGLE},{6,BOND::SINGLE},{6,BOND::AMIDE}},"N.pl3",true,false,0},///6U4
    {7,{{1,BOND::SINGLE},{6,BOND::SINGLE},{8,BOND::SINGLE}},"N.3",false,false,0},///FP1:N2
    {7,{{1,BOND::SINGLE},{6,BOND::SINGLE},{8,BOND::SINGLE}},"N.3",true,false,0},///G98:N5
    {7,{{1,BOND::SINGLE},{15,BOND::SINGLE},{15,BOND::SINGLE}},"N.3",false,false,0},///GGM:N3B
    {7,{{1,BOND::SINGLE},{1,BOND::SINGLE},{15,BOND::SINGLE}},"N.3",false,false,0},///GNH:N3B
    {7,{{1,BOND::SINGLE},{6,BOND::SINGLE},{8,BOND::SINGLE}},"N.3",false,false,0},///HAR:NH1
    {7,{{6,BOND::SINGLE},{6,BOND::DOUBLE},{8,BOND::SINGLE}},"N.pl3",true,true,0},///1WT:N22
    {7,{{6,BOND::AMIDE},{1,BOND::SINGLE},{7,BOND::SINGLE}},"N.ar",true,true,0},///2KW:N20
    {7,{{6,BOND::DOUBLE},{6,BOND::SINGLE},{8,BOND::SINGLE}},"N.ar",true,true,0},///3JI:N1
    {7,{{1,BOND::SINGLE},{6,BOND::SINGLE},{5,BOND::SINGLE}},"N.ar",true,true,0},///6OQ:N04
    {7,{{1,BOND::SINGLE},{1,BOND::SINGLE},{6,BOND::SINGLE},{77,BOND::SINGLE}},"N.4",true,false,1},///4IR:N5
    {7,{{1,BOND::SINGLE},{1,BOND::SINGLE},{6,BOND::SINGLE},{77,BOND::SINGLE}},"N.4",true,false,1},///5IR:N5
    {7,{{1,BOND::SINGLE},{6,BOND::SINGLE},{6,BOND::SINGLE},{29,BOND::SINGLE}},"N.4",true,false,1},///MM1:N4
    {7,{{6,BOND::SINGLE},{6,BOND::SINGLE},{6,BOND::SINGLE},{29,BOND::SINGLE}},"N.4",true,false,1},///MM1:N4
    {7,{{1,BOND::SINGLE},{1,BOND::SINGLE},{6,BOND::SINGLE},{75,BOND::SINGLE}},"N.4",true,false,1},///REJ:N
    {7,{{6,BOND::SINGLE},{6,BOND::SINGLE},{6,BOND::SINGLE},{29,BOND::SINGLE}},"N.4",true,true,1},///MP1:N1
    {7,{{6,BOND::SINGLE},{6,BOND::AROMATIC_BD},{6,BOND::AROMATIC_BD},{29,BOND::SINGLE}},"N.4",true,true,1},///MP1:N1
    {7,{{1,BOND::SINGLE},{6,BOND::SINGLE},{6,BOND::SINGLE},{44,BOND::SINGLE}},"N.pl3",true,true,0},///HB1
    {7,{{6,BOND::SINGLE},{6,BOND::DOUBLE},{75,BOND::SINGLE}},"N.ar",true,true,1},///REP:N1
    {7,{{1,BOND::SINGLE},{6,BOND::SINGLE},{75,BOND::SINGLE}},"N.3",true,false,0},///RHM:N1

    {7,{{6,BOND::SINGLE},{6,BOND::DOUBLE},{77,BOND::SINGLE}},"N.4",true,false,1},///8TH:N21
    {7,{{1,BOND::SINGLE},{5,BOND::SINGLE},{6,BOND::SINGLE}},"N.pl3",true,false,1},///B20
    {7,{{1,BOND::SINGLE},{7,BOND::DOUBLE}},"N.2",false,false,0},///FUY:N2
    {7,{{1,BOND::SINGLE},{1,BOND::SINGLE},{30,BOND::SINGLE}},"N.3",false,false,0},///DAZ
    {7,{{7,BOND::DOUBLE},{16,BOND::SINGLE}},"N.ar",true,true,0},///0DQ:N29
    {7,{{6,BOND::DOUBLE},{16,BOND::SINGLE}},"N.ar",true,true,0},///0HO:NAG
    {7,{{1,BOND::SINGLE},{16,BOND::DOUBLE}},"N.2",false,false,0},///2TG:N5
    {7,{{1,BOND::SINGLE},{6,BOND::AMIDE},{6,BOND::AMIDE}},"N.am",true,false,0},///RDD:N5
    {7,{{1,BOND::SINGLE},{16,BOND::SINGLE},{16,BOND::SINGLE}},"N.3",false,false,0},///K1R:NAF
    {7,{{6,BOND::SINGLE},{16,BOND::SINGLE},{44,BOND::SINGLE}},"N.ar",true,true,0},///KYS:N26
    {7,{{15,BOND::SINGLE},{16,BOND::DOUBLE}},"N.2",false,false,0},///LBP:NS
    {7,{{1,BOND::SINGLE},{15,BOND::DOUBLE}},"N.2",false,false,0},///PON:N
    {7,{{7,BOND::SINGLE},{16,BOND::DOUBLE}},"N.2",true,true,0},///V21:N14
    {7,{{7,BOND::SINGLE},{7,BOND::DOUBLE}},"N.2",true,true,0},///TKL:NZ
    {7,{{1,BOND::SINGLE},{6,BOND::SINGLE},{45,BOND::SINGLE}},"N.3",true,false,0},///RHM:N1
    {7,{{1,BOND::SINGLE},{1,BOND::SINGLE},{1,BOND::SINGLE}},"N.3",false,false,0},///NH3
    {7,{{1,BOND::SINGLE},{6,BOND::SINGLE},{16,BOND::SINGLE}},"N.3",true,false,0},///LTI:N2
    {7,{{6,BOND::SINGLE},{6,BOND::SINGLE},{44,BOND::SINGLE}},"N.ar",true,true,0},///HB1:N1
    {7,{{1,BOND::SINGLE},{1,BOND::SINGLE},{76,BOND::SINGLE}},"N.3",false,false,0},///OHX:N1
    {7,{{1,BOND::SINGLE},{6,BOND::SINGLE},{44,BOND::SINGLE}},"N.3",true,false,0},///OD1:N27
    {7,{{1,BOND::SINGLE},{7,BOND::SINGLE},{8,BOND::SINGLE}},"N.3",false,false,0},///YX1:NAO
    {7,{{1,BOND::SINGLE},{1,BOND::SINGLE},{17,BOND::SINGLE}},"N.3",false,false,0},///TKL:NZ
    {7,{{16,BOND::DOUBLE},{6,BOND::SINGLE}},"N.2",false,false,0},///3415917
    {7,{{1,BOND::SINGLE},{6,BOND::SINGLE},{6,BOND::DELOCALIZED}},"N.pl3",false,false,0},///ARG:NE
    {7,{{1,BOND::SINGLE},{1,BOND::SINGLE},{6,BOND::DELOCALIZED}},"N.pl3",false,false,0},///ARG:NH1
    {7,{{1,BOND::SINGLE},{7,BOND::DOUBLE},{7,BOND::SINGLE}},"N.pl3",true,false,1},
    {7,{{1,BOND::SINGLE},{7,BOND::DOUBLE}},"N.2",false,false,0},
    {7,{{8,BOND::SINGLE},{7,BOND::DOUBLE}},"N.2",true,false,0},
    {7,{{17,BOND::SINGLE},{7,BOND::DOUBLE}},"N.2",false,false,0},
    {7,{{6,BOND::SINGLE},{6,BOND::DOUBLE},{16,BOND::SINGLE}},"N.2",true,false,1},
    {7,{{6,BOND::DOUBLE},{7,BOND::SINGLE},{44,BOND::SINGLE}},"N.pl3",true,false,1},//7GE N+
    {7,{{6,BOND::SINGLE},{6,BOND::SINGLE},{6,BOND::SINGLE},{65,BOND::SINGLE},},"N.4",true,false,0},//7MT N
    {7,{{6,BOND::AROMATIC_BD},{6,BOND::AROMATIC_BD},{15,BOND::SINGLE}},"N.pl3",true,true,0},//Q1V:N13

};

bool protspace::AtomPerception::perceiveSingleNitrogen(MMAtom& atom)
{
        try{



        if (mAtStat.numBond()==4 && mAtStat.numSing()==4 && !mAtStat.isArRing())
        {
       //     std::cout <<atom.getResidue().getName()<<"\t"<<atom.getName()<<"\t"<<"NITRO_0"<<std::endl;
        setMOL2(atom,"N.4"); atom.setFormalCharge(+1);return true;}
        if (mAtStat.numBond()==2  && mAtStat.numDouble()==2
                && !mAtStat.isRing())
        {
           // std::cout <<atom.getResidue().getName()<<"\t"<<atom.getName()<<"\t"<<"NITRO_1"<<std::endl;
        setMOL2(atom,"N.4"); atom.setFormalCharge(+1);
        return true;}
        else if (mAtStat.numBd()==0)
        {
         //   std::cout <<atom.getResidue().getName()<<"\t"<<atom.getName()<<"\t"<<"NITRO_2"<<std::endl;
            setMOL2(atom,"N.4");atom.setFormalCharge(+1);return true;
        }
        else if (mAtStat.numBond()==3 && mAtStat.numSing()==3 &&
                 mAtStat.numHeavy()==3 &&  !mAtStat.isRing())
        {
        //    std::cout <<atom.getResidue().getName()<<"\t"<<atom.getName()<<"\t"<<"NITRO_3"<<std::endl;
        setMOL2(atom,"N.3");return true;}
        else if (mAtStat.numBond()==3 && mAtStat.numSing()==2 && mAtStat.numAmide()==1 &&
                 !mAtStat.isRing())
        {
            setMOL2(atom,"N.3");return true;
        }
        else if (mAtStat.isArRing() && mAtStat.isRing())
        {
            switch (mAtStat.numBd())
            {
            case 2:
                if (mAtStat.numAr()==2){setMOL2(atom,"N.ar");return true;}
                break;
            case 3:
                if (mAtStat.numAr()==2 && mAtStat.numSing()==1){setMOL2(atom,"N.pl3");return true;}
                break;
            }
        }
        else if (mAtStat.numBond()==0){setMOL2(atom,"N.3");return true;}
size_t pos=0;
            for(const AtomRule& entry:mNitrogenRules)
            {
                ++pos;++mAllCounts;
                if (!followRule(entry,atom))continue;
             //   std::cout <<atom.getResidue().getName()<<"\t"<<atom.getName()<<"\t"<<"NR_"<<pos-1<<std::endl;
                setMOL2(atom,entry.mMOL2);
                mCountsN.at(pos-1)++;
                return true;
            }

            ELOG_ERR("Unrecognized atom "+atom.toString());
            LOG_ERR("Unrecognized atom "+atom.getParent().getName()+" - "+atom.getIdentifier()+"\n"+atom.toString());
            atom.setMOL2Type("Du");
            return false;

        return true;
    }catch(ProtExcept &e)
    {
        e.addHierarchy("AtomPerc::perceiveSingleOxygen");
        throw;
    }
}
bool protspace::AtomPerception::processNitrogen(MMAtom& atom)
{
    try{
        if (mGroupPerception && isNOxide(atom))return true;
        return isNitro(atom);
    }catch(ProtExcept &e)
    {
        e.addHierarchy("AtomPerc::processNitrogen");
        throw;
    }
}



bool protspace::AtomPerception::isNOxide(MMAtom& atom)
{
    try{
        if (!(mAtStat.numOx() ==1 &&
              mAtStat.numBd() ==3 &&
              mAtStat.numHy() ==0 &&
             (mAtStat.numC()  ==1||mAtStat.numC()==2) &&
              !mAtStat.isArRing()))return false;
        bool isDbBond=false;
        MMAtom *atomO=nullptr, *atomA1=nullptr, *atomA2=nullptr;
        \
        for (size_t i=0;i<3;++i)
        {
            MMAtom& atomL1 = atom.getAtom(i);
            const MMBond& bondL1=atom.getBond(i);
            if (bondL1.getType()==BOND::DOUBLE)isDbBond=true;
            if (atomL1.isOxygen())
            {
                // Oxygen can only be linked to nitrogen
                if (atomL1.numBonds()>1){return false;}
                atomO=&atomL1;
            }
            else if( atomA1==nullptr)atomA1=&atomL1;
            else if( atomA2==nullptr)atomA2=&atomL1;
        }


        if (!isDbBond|| atomO==nullptr)return false;
        atom.setFormalCharge(1);
        if (atomA1->getBond(atom).getType()==BOND::DOUBLE)
        {
            atomA2->getBond(atom).setBondType(BOND::SINGLE);
            setMOL2(atom,"N.2");
        }
        else if (atomA2->getBond(atom).getType()==BOND::DOUBLE)
        {
            atomA1->getBond(atom).setBondType(BOND::SINGLE);
            setMOL2(atom,"N.2");

        }
        else
        {
            setMOL2(atom,(mAtStat.numC()==1)?"N.pl3":"N.2");
            //std::cout <<atom.getResidue().getName()<<"\t"<<atom.getName()<<"\t"<<"NOX"<<std::endl;
           // std::cout <<atomO->getResidue().getName()<<"\t"<<atomO->getName()<<"\t"<<"NOX"<<std::endl;
            atom.getBond(*atomA1).setBondType(BOND::AROMATIC_BD);
            atom.getBond(*atomA2).setBondType(BOND::AROMATIC_BD);
        }
        setMOL2(*atomO,"O.2");
        return true;
    }catch(ProtExcept &e)
    {
        e.addHierarchy("AtomPerc::isNOxide");
        throw;
    }
}

bool protspace::AtomPerception::isNitro(MMAtom& atom)
{
    try{
        if (!(mAtStat.numOx()>=2 && (mAtStat.numBd()==3 || mAtStat.numBd()==2)))return false;
        if (mAtStat.numOx()==3)
        {
            bool done=false;
            for (size_t i=0;i<3;++i)
            {
                MMAtom& atomL1 = atom.getAtom(i);
                if (protspace::numHeavyAtomBonded(atomL1)>1)setMOL2(atomL1,"O.3");
                else if (!done){
                  //  std::cout <<atomL1.getResidue().getName()<<"\t"<<atomL1.getName()<<"\t"<<"NITRO"<<std::endl;

                    setMOL2(atomL1,"O.2");atom.getBond(i).setBondType(BOND::DOUBLE);done=true;}
                else {
                  //  std::cout <<atomL1.getResidue().getName()<<"\t"<<atomL1.getName()<<"\t"<<"NITRO"<<std::endl;
                    setMOL2(atomL1,"O.3");atom.getBond(i).setBondType(BOND::SINGLE);}
            }
            setMOL2(atom,"N.pl3");
           // std::cout <<atom.getResidue().getName()<<"\t"<<atom.getName()<<"\t"<<"NITRO"<<std::endl;
            return true;

        }
        if(mAtStat.numBd()==3)
            for (size_t i=0;i<3;++i)
            {
                MMAtom& atomL1 = atom.getAtom(i);
                MMBond& bond = atom.getBond(i);
                if (!atomL1.isOxygen()) bond.setBondType(BOND::SINGLE);
                else{
             //       std::cout <<atomL1.getResidue().getName()<<"\t"<<atomL1.getName()<<"\t"<<"NITRO"<<std::endl;
                    setMOL2(atomL1,"O.2");
                    bond.setBondType(BOND::DOUBLE);
                }

            }

        if (mAtStat.numBd()==2 )

            for (size_t i=0;i<2;++i)
            {
                MMAtom& atomL1 = atom.getAtom(i);
                MMBond& bond = atom.getBond(i);
                if (atomL1.numBonds()==2)
                {
               //     std::cout <<atomL1.getResidue().getName()<<"\t"<<atomL1.getName()<<"\t"<<"NITRO"<<std::endl;
                    setMOL2(atomL1,"O.3");
                    bond.setBondType(BOND::SINGLE);
                }
                else
                {
             //       std::cout <<atomL1.getResidue().getName()<<"\t"<<atomL1.getName()<<"\t"<<"NITRO"<<std::endl;
                     setMOL2(atomL1,"O.2");
                    bond.setBondType(BOND::DOUBLE);
                }
            }
       // std::cout <<atom.getResidue().getName()<<"\t"<<atom.getName()<<"\t"<<"NITRO"<<std::endl;
         setMOL2(atom,"N.pl3");
        return true;
    }catch(ProtExcept &e)
    {
        e.addHierarchy("AtomPerc::isNitro");
        throw;
    }
}






