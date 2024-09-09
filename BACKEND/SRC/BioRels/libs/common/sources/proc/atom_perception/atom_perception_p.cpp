#include "headers/proc/atomperception.h"
#include "headers/statics/intertypes.h"
#include "headers/molecule/mmatom.h"
#include "headers/molecule/mmbond.h"
#include "headers/molecule/macromole.h"
#include "headers/molecule/mmatom_utils.h"
#include "headers/statics/logger.h"
#undef NDEBUG /// Active assertion in release



bool protspace::AtomPerception::processPhosphate(MMAtom& atom)
{
    try{
        atom.setMOL2Type("P.3");
        if (mAtStat.numOx()==4 && mAtStat.numBd()==4)
        {
            size_t nBridge=0;
            bool bridge[4]={false};
            for (size_t iAtmT =0; iAtmT < mAtStat.numBd(); ++iAtmT)
            {
                MMAtom& atomL1 = atom.getAtom(iAtmT);
                if (atomL1.numBonds()==2)
                {
                    const size_t nH=numHydrogenAtomBonded(atomL1);

                    if (nH==0){nBridge++;bridge[iAtmT]=true;}
                    else bridge[iAtmT]=false;
                }else bridge[iAtmT]=false;
            }
            if (nBridge==0)
            {
                for (size_t iAtm2 =0; iAtm2 < mAtStat.numBd(); ++iAtm2)
                {
                    MMAtom& atomL1 = atom.getAtom(iAtm2);
                    if (atomL1.getName()=="O1")
                    {
                      //  std::cout <<atomL1.getResidue().getName()<<"\t"<<atomL1.getName()<<"\t"<<"PHOSPHATE"<<std::endl;
                        setMOL2(atomL1,"O.2");
                        atomL1.getBond(atom).setBondType(BOND::DOUBLE);

                    }
                    else
                    {
                    //    std::cout <<atomL1.getResidue().getName()<<"\t"<<atomL1.getName()<<"\t"<<"PHOSPHATE"<<std::endl;
                        setMOL2(atomL1,"O.3");
                        atomL1.getBond(atom).setBondType(BOND::SINGLE);
                        if(atomL1.numBonds()==1)atomL1.setFormalCharge(-1);
                    }
                }
            }
            else{

                for (size_t iAtm2 =0; iAtm2 < mAtStat.numBd(); ++iAtm2)
                {
                    MMAtom& atomL1 = atom.getAtom(iAtm2);
                    if (atomL1.numBonds()==2 && bridge[iAtm2])
                    {
                      //  std::cout <<atomL1.getResidue().getName()<<"\t"<<atomL1.getName()<<"\t"<<"PHOSPHATE"<<std::endl;
                        setMOL2(atomL1,"O.3");
                        atomL1.getBond(atom).setBondType(BOND::SINGLE);
                    }
                    else
                    {
                      //  std::cout <<atomL1.getResidue().getName()<<"\t"<<atomL1.getName()<<"\t"<<"PHOSPHATE"<<std::endl;
                        setMOL2(atomL1,"O.co2");
                        atomL1.getBond(atom).setBondType(BOND::AROMATIC_BD);

                    }

                }
            }//ELSE BRIGE
            return true;
        }//END PHOSPHATE GROUP
        else if (mAtStat.numOx()==3 && mAtStat.numHy()==1 && mAtStat.numBd()==4)
        {// CASE 2PB
            for (size_t iAtm2 =0; iAtm2 < mAtStat.numBd(); ++iAtm2)
            {
                MMAtom& atomL1 = atom.getAtom(iAtm2);
                if (!atomL1.isOxygen()) continue;
                //std::cout <<atomL1.getResidue().getName()<<"\t"<<atomL1.getName()<<"\t"<<"PHOSPHATE"<<std::endl;
                setMOL2(atomL1,(atomL1.numBonds()==2)?"O.3":"O.co2");
            }
            return true;
        }


        return true;
    }catch(ProtExcept &e)
    {
        e.addHierarchy("AtomPerc::processPhosphate");
        throw;
    }
}
