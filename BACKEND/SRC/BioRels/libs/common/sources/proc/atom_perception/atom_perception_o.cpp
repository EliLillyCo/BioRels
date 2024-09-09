#include "headers/proc/atomperception.h"
#include "headers/statics/intertypes.h"
#include "headers/molecule/mmatom.h"
#include "headers/molecule/mmbond.h"
#include "headers/molecule/macromole.h"
#include "headers/molecule/mmatom_utils.h"
#include "headers/statics/logger.h"
#undef NDEBUG /// Active assertion in release
///TODO Check CU6
const std::vector<protspace::AtomRule> protspace::AtomPerception::mOxygenRules={
    {8,{{6,BOND::SINGLE},{6,BOND::DOUBLE}},"O.2",true,false,1},///DF4:O6
    {8,{{1,BOND::SINGLE}},"O.3",false,false,-1},
    {8,{{8,BOND::DOUBLE},{26,BOND::SINGLE},{28,BOND::SINGLE}},"O.2",false,false,2},///NFC:O4
    {8,{{74,BOND::DOUBLE},{74,BOND::SINGLE},{74,BOND::SINGLE}},"O.2",false,false,2},///WO3:O
    {8,{{6,BOND::SINGLE},{6,BOND::SINGLE}},"O.3",false,false,0}, ///017:O23
    {8,{{6,BOND::SINGLE},{15,BOND::SINGLE}},"O.3",false,false,0},///02E:OP2/OP3
    {8,{{6,BOND::SINGLE},{7,BOND::SINGLE}},"O.2",true,true,0},///02J:01
    {8,{{6,BOND::AROMATIC_BD},{7,BOND::AROMATIC_BD}},"O.2",true,true,0},///02J:O1
    {8,{{5,BOND::SINGLE},{6,BOND::SINGLE}},"O.3",false,false,0},///0A2:O11/O12
    {8,{{1,BOND::SINGLE},{1,BOND::SINGLE}},"O.3",false,false,0},///HOH
    {8,{{1,BOND::SINGLE},{1,BOND::SINGLE},{1,BOND::SINGLE}},"O.3",false,false,1},///D3O
    {8,{{6,BOND::SINGLE},{23,BOND::SINGLE},{23,BOND::SINGLE}},"O.3",true,false,0},///DVG:O2
    {8,{{6,BOND::AROMATIC_BD},{6,BOND::AROMATIC_BD}},"O.2",true,true,0},///00J:031
    {8,{{7,BOND::AROMATIC_BD},{7,BOND::AROMATIC_BD}},"O.2",true,true,0},///0X3:OAB
    {8,{{7,BOND::SINGLE},{7,BOND::SINGLE}},"O.3",true,false,0},///3C3:O15
   {8,{{6,BOND::SINGLE},{23,BOND::SINGLE}},"O.3",false,false,0},///AIV:O2
    {8,{{6,BOND::SINGLE},{42,BOND::SINGLE}},"O.3",true,false,0},///CUB:OM2
    {8,{{23,BOND::SINGLE},{23,BOND::SINGLE}},"O.3",true,false,0},///DVG:O9
    {8,{{42,BOND::SINGLE},{42,BOND::SINGLE}},"O.3",true,false,0},///M27:O2
    {8,{{6,BOND::SINGLE},{8,BOND::SINGLE}},"O.3",true,false,0},///MC8:O30
    {8,{{33,BOND::SINGLE},{42,BOND::SINGLE}},"O.3",true,false,0},///RMO:O1
    {8,{{74,BOND::SINGLE},{74,BOND::SINGLE}},"O.3",true,false,0},///WO3:O
    {8,{{74,BOND::SINGLE},{74,BOND::SINGLE},{74,BOND::SINGLE}},"O.3",true,false,1},///WO3:O
    {8,{{74,BOND::DOUBLE},{74,BOND::DOUBLE},{74,BOND::DOUBLE}},"O.3",true,false,0},///WO3:O
    {8,{{74,BOND::DOUBLE},{74,BOND::DOUBLE}},"O.3",true,false,0},///WO3:O
    {8,{{6,BOND::AROMATIC_BD}},"O.co2",false,false,0},
    {8,{{15,BOND::AROMATIC_BD}},"O.co2",false,false,0},///SXM:O10
    {8,{{8,BOND::SINGLE},{42,BOND::SINGLE}},"O.3",true,false,0},///6MO
    {8,{{15,BOND::SINGLE},{12,BOND::SINGLE}},"O.3",true,false,0},///APW:O2A/O2B
    {8,{{6,BOND::SINGLE},{29,BOND::SINGLE}},"O.3",true,false,0},///B15:O3/O4
    {8,{{7,BOND::SINGLE},{26,BOND::SINGLE}},"O.3",true,false,0},///CM1:O44/O46
    {8,{{6,BOND::SINGLE},{26,BOND::SINGLE}},"O.3",true,false,0},///CM1:O44/O46
    {8,{{26,BOND::SINGLE},{26,BOND::SINGLE}},"O.3",true,false,0},///CNB
    {8,{{29,BOND::SINGLE},{29,BOND::SINGLE}},"O.3",true,false,0},///CUO
    {8,{{23,BOND::SINGLE},{23,BOND::SINGLE},{23,BOND::SINGLE}},"O.3",true,false,1},///DVT
    {8,{{23,BOND::SINGLE},{23,BOND::SINGLE},{23,BOND::SINGLE},{23,BOND::SINGLE},{23,BOND::SINGLE},{23,BOND::SINGLE}},"O.3",true,false,4},///DVT
    {8,{{74,BOND::SINGLE},{74,BOND::SINGLE},{74,BOND::SINGLE}},"O.3",true,false,1},///DVT
    {8,{{42,BOND::SINGLE},{42,BOND::SINGLE},{42,BOND::SINGLE}},"O.3",true,false,1},///WSQ
    {8,{{42,BOND::SINGLE},{42,BOND::SINGLE},{42,BOND::SINGLE},{42,BOND::SINGLE}},"O.3",true,false,2},///WSQ
    {8,{{40,BOND::SINGLE},{40,BOND::SINGLE},{40,BOND::SINGLE},{40,BOND::SINGLE}},"O.3",true,false,2},///ZKG
    {8,{{40,BOND::SINGLE},{40,BOND::SINGLE},{40,BOND::SINGLE}},"O.3",true,false,2},///ZKG
    {8,{{7,BOND::SINGLE},{23,BOND::SINGLE}},"O.3",true,false,0},///BVA
    {8,{{8,BOND::SINGLE},{26,BOND::SINGLE},{26,BOND::SINGLE}},"O.3",true,false,1},///CN1
    {8,{{8,BOND::SINGLE},{26,BOND::SINGLE}},"O.3",true,false,0},///CN1
    {8,{{26,BOND::SINGLE},{26,BOND::SINGLE},{26,BOND::SINGLE}},"O.3",true,false,1},///CNF
    {8,{{6,BOND::SINGLE},{26,BOND::SINGLE},{26,BOND::SINGLE}},"O.3",true,false,1},///FDC
    {8,{{7,BOND::SINGLE},{31,BOND::SINGLE}},"O.3",true,false,0},///GCR
    {8,{{40,BOND::SINGLE},{40,BOND::SINGLE},{40,BOND::SINGLE},{40,BOND::SINGLE},{40,BOND::SINGLE},{40,BOND::SINGLE},{40,BOND::SINGLE}},"O.3",false,false,2},///ZRC
    {8,{{72,BOND::SINGLE},{72,BOND::SINGLE}},"O.3",false,false,0},///HF3
    {8,{{72,BOND::SINGLE},{72,BOND::SINGLE},{72,BOND::SINGLE}},"O.3",false,false,1},///HF3
    {8,{{6,BOND::SINGLE},{28,BOND::SINGLE}},"O.3",true,false,0},///NUF
    {8,{{6,BOND::SINGLE},{20,BOND::SINGLE},{25,BOND::SINGLE},{25,BOND::SINGLE}},"O.3",true,false,2},///OEC
    {8,{{20,BOND::SINGLE},{25,BOND::SINGLE},{25,BOND::SINGLE},{25,BOND::SINGLE}},"O.3",true,false,2},///OEC
    {8,{{25,BOND::SINGLE},{25,BOND::SINGLE},{25,BOND::SINGLE}},"O.3",true,false,1},///OEC
    {8,{{20,BOND::SINGLE},{25,BOND::SINGLE},{25,BOND::SINGLE}},"O.3",true,false,1},///OEC
    {8,{{25,BOND::SINGLE},{25,BOND::SINGLE}},"O.3",true,false,0},///OER
    {8,{{25,BOND::SINGLE},{25,BOND::SINGLE},{25,BOND::SINGLE},{38,BOND::SINGLE}},"O.3",true,false,2},///OER
    {8,{{25,BOND::SINGLE},{25,BOND::SINGLE},{38,BOND::SINGLE}},"O.3",true,false,1},///OER
    {8,{{72,BOND::SINGLE},{72,BOND::SINGLE},{72,BOND::SINGLE},{72,BOND::SINGLE}},"O.3",true,false,2},///PHF
    {8,{{6,BOND::SINGLE},{78,BOND::SINGLE}},"O.3",true,false,0},///QPT
    {8,{{6,BOND::SINGLE},{75,BOND::SINGLE}},"O.3",true,false,0},///REJ
    {8,{{6,BOND::DOUBLE},{44,BOND::SINGLE}},"O.2",true,false,1},///RU0
    {8,{{6,BOND::SINGLE},{44,BOND::SINGLE}},"O.3",true,false,0},///RU0
    {8,{{6,BOND::SINGLE},{24,BOND::SINGLE}},"O.3",true,false,0},///TIL
    {8,{{6,BOND::SINGLE},{39,BOND::SINGLE}},"O.3",true,false,0},///YBT
    {8,{{74,BOND::SINGLE},{72,BOND::SINGLE}},"O.3",true,false,0},///HFW
    {8,{{52,BOND::SINGLE},{74,BOND::SINGLE},{74,BOND::SINGLE}},"O.3",true,false,0},///TEW
    {8,{{74,BOND::SINGLE},{74,BOND::SINGLE},{74,BOND::SINGLE}},"O.3",true,true,0},///E43
    {8,{{1,BOND::SINGLE},{74,BOND::SINGLE},{74,BOND::SINGLE},{74,BOND::SINGLE}},"O.3",true,true,0},///E43
    {8,{{6,BOND::SINGLE},{6,BOND::SINGLE},{46,BOND::SINGLE}},"O.3",true,false,0},///SXC
    {8,{{6,BOND::DELOCALIZED}},"O.co2",false,false,0},///2r59:O33
    {8,{{15,BOND::DELOCALIZED}},"O.co2",false,false,0},///2r59
    {8,{{6,BOND::SINGLE},{15,BOND::AROMATIC_BD}},"O.3",false,false,0},///1CC
    {8,{{1,BOND::SINGLE},{15,BOND::AROMATIC_BD}},"O.3",false,false,0},///1FC
    {8,{{6,BOND::SINGLE},{24,BOND::SINGLE}},"O.3",true,false,0},///AC9
    {8,{{6,BOND::SINGLE},{1,BOND::SINGLE},{39,BOND::SINGLE}},"O.3",true,false,1},///YBT:O1
    {8,{{6,BOND::DOUBLE},{24,BOND::SINGLE}},"O.3",true,false,1},///AC9:O1
    {8,{{74,BOND::SINGLE},{74,BOND::SINGLE},{74,BOND::SINGLE},{74,BOND::SINGLE}},"O.3",true,false,2},///E43
    {8,{{1,BOND::SINGLE},{6,BOND::SINGLE},{26,BOND::SINGLE},{26,BOND::SINGLE}},"O.3",true,false,2},///FDC
    {8,{{1,BOND::SINGLE},{6,BOND::DOUBLE}},"O.2",false,false,1},
    {8,{{6,BOND::SINGLE},{65,BOND::SINGLE}},"O.3",true,false,0},///7MT:O26
};

bool protspace::AtomPerception::perceiveSingleOxygen(MMAtom& atom)
{
        try{
            if (!mAtStat.isRing() &&
                 mAtStat.numBd()==1&&
                    mAtStat.numHeavy()==1&&
                    mAtStat.numSing()==1)
            {
               // std::cout <<atom.getResidue().getName()<<"\t"<<atom.getName()<<"\t"<<"OX_0"<<std::endl;
                setMOL2(atom,"O.3");
                return true;
            }
            else if (!mAtStat.isRing() &&
                     mAtStat.numBd()==1&&
                     mAtStat.numDouble()==1)
            {
              //  std::cout <<atom.getResidue().getName()<<"\t"<<atom.getName()<<"\t"<<"OX_1"<<std::endl;
                setMOL2(atom,"O.2");
                return true;
            }
            else if (!mAtStat.isRing() &&
                     mAtStat.numBd()==0)
            {
               // std::cout <<atom.getResidue().getName()<<"\t"<<atom.getName()<<"\t"<<"OX_2"<<std::endl;
                setMOL2(atom,"O.3");
                return true;
            }
            else if (!mAtStat.isRing() &&
                     mAtStat.numBd()==2&&
                     mAtStat.numSing()==2)
            {
               // std::cout <<atom.getResidue().getName()<<"\t"<<atom.getName()<<"\t"<<"OX_3"<<std::endl;
                setMOL2(atom,"O.3");
                return true;
            }
            else if (!mAtStat.isRing() &&
                     mAtStat.numBd()==1&&
                     mAtStat.numTriple()==1)
            {
              //  std::cout <<atom.getResidue().getName()<<"\t"<<atom.getName()<<"\t"<<"OX_4"<<std::endl;
                setMOL2(atom,"O.2");
                atom.setFormalCharge(1);
                return true;///2T8:O
            }
            else if (!mAtStat.isRing() && mAtStat.numBd()==3 && mAtStat.numSing()==3)
            {
               // std::cout <<atom.getResidue().getName()<<"\t"<<atom.getName()<<"\t"<<"OX_5"<<std::endl;
                setMOL2(atom,"O.2");
                atom.setFormalCharge(1);
                return true;
            }
            else if (!mAtStat.isRing() && mAtStat.numBd()==3 && mAtStat.numSing()==3
                     && mAtStat.numHy()==3)
            {
                //std::cout <<atom.getResidue().getName()<<"\t"<<atom.getName()<<"\t"<<"OX_6"<<std::endl;
                setMOL2(atom,"O.3");
                atom.setFormalCharge(1);
                return true;
            }
            else if (!mAtStat.isRing() && mAtStat.numBd()==2 && mAtStat.numSing()==2
                     && mAtStat.numHy()==2)
            {
                //std::cout <<atom.getResidue().getName()<<"\t"<<atom.getName()<<"\t"<<"OX_7"<<std::endl;
                setMOL2(atom,"O.3");

                return true;
            }


size_t pos=0;
            for(const AtomRule& entry:mOxygenRules)
            {++pos;++mAllCounts;
                if (!followRule(entry,atom))continue;
             //   std::cout <<atom.getResidue().getName()<<"\t"<<atom.getName()<<"\t"<<"OR_"<<pos-1<<std::endl;
                setMOL2(atom,entry.mMOL2);mCountsO.at(pos-1)++;
                return true;
            }



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

