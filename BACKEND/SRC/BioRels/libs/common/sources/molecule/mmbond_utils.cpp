#include "headers/molecule/macromole.h"
#include "headers/molecule/mmbond_utils.h"
#include "headers/statics/intertypes.h"
#include "headers/statics/logger.h"

namespace protspace
{
const std::string& getBondType(const MMBond& bond)
{
    const uint16_t& type(bond.getType());
    const auto it=BOND::typeToName.find(type);
    if (it == BOND::typeToName.end())
        throw_line("305001",
                   "MMBond::utils::getBondType",
                   "Type not found");
    return (*it).second;
}

void removeAllBondsFromAtom(MMAtom& atom)
try{
    std::vector<MMBond*> listBd;
    for(size_t iBd=0;iBd<atom.numBonds();++iBd)
    {
        MMBond& bond=atom.getBond(iBd);
        listBd.push_back(&bond);
    }
    for(size_t i=0;i<listBd.size();++i)
    {
        ELOG("Deletion of "+listBd.at(i)->toString()+" to maintain valence");
        atom.getParent().delBond(*listBd.at(i));
    }
}catch(ProtExcept &e)
{
    assert(e.getId()!="310201" && e.getId()!="071001");///Bond must exists
    assert(e.getId()!="350701" && e.getId()!="350702");///Bond must be part of molecule
    assert(e.getId()!="020101" && e.getId()!="020201");///Atom bond must exists
    e.addHierarchy("MMBondUtils::removeAllBondsFromAtom");
    throw;
}

uint16_t getMMBondType(const std::string bondType) {
    uint16_t b = BOND::UNDEFINED;
    if (bondType == "1") {
        b = BOND::SINGLE;
    }
    else if (bondType == "2") {
        b = BOND::DOUBLE;
    }
    else if (bondType == "3") {
        b = BOND::TRIPLE;
    }
    else if (bondType == "de") {
        b = BOND::DELOCALIZED;
    }
    else if (bondType == "ar") {
        b = BOND::AROMATIC_BD;
    }
    else if (bondType == "du") {
        b = BOND::DUMMY;
    }
    else if (bondType == "fu") {
        b = BOND::FUSED;
    }
    else if (bondType == "am") {
        b = BOND::AMIDE;
    }
    else if (bondType == "4") {
        b = BOND::QUADRUPLE;
    }
    return b;
}

} // namespace
