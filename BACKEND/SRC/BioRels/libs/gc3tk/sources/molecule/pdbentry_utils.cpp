#include "headers/molecule/pdbentry_utils.h"
#include "headers/statics/intertypes.h"
#include "headers/molecule/macromole_utils.h"
#include "headers/molecule/hetmanager.h"
#include "headers/proc/bondperception.h"
#include "headers/proc/matchtemplate.h"
#include "headers/parser/writerMOL2.h"
namespace protspace
{

void prepareMolecule(MacroMole& mole, const uint32_t& rules,const bool& isInternal)
{
    try{

        if ((rules&PREPRULE::SELENOMET)==PREPRULE::SELENOMET) convertMSE_MET(mole);
        if ((rules&PREPRULE::SELENOCYS)==PREPRULE::SELENOCYS)convertCSE_CYS(mole);

        if ((rules&PREPRULE::ASSIGN_RESTYPE)==PREPRULE::ASSIGN_RESTYPE)
        {
            protspace::HETManager& hetmanager=protspace::HETManager::Instance();
            hetmanager.assignResidueType(mole,isInternal,false);
        }
        if ((rules&PREPRULE::CONNECT)==PREPRULE::CONNECT)
        {
            BondPerception perc;
            perc.processMolecule(mole);
        }
        if((rules&PREPRULE::ASSIGN_ATMTYPE)==PREPRULE::ASSIGN_ATMTYPE)
        {
            MatchTemplate matcht;
            matcht.setIsInternal(isInternal);
            matcht.processMolecule(mole);
        }
        for(size_t iBd=0;iBd < mole.numBonds();++iBd)
        {
            MMBond& bond=mole.getBond(iBd);
            if (bond.getType()!=BOND::UNDEFINED)continue;
            LOG_ERR("Unrecognized bond type - Set to single "+bond.toString());
            ELOG_ERR("Unrecognized bond type - Set to single "+bond.toString());
            bond.setBondType(BOND::SINGLE);
        }
    }catch(ProtExcept &e)
    {
        e.addHierarchy("prepareMolecule");
        throw;
    }

}// END PREPARE MOLECULE


}

