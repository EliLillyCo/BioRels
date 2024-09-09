#include "headers/iwcore/iwicf.h"
#include "aromatic.h"
#include "iwstring_data_source.h"
#include "path.h"
#include "headers/statics/intertypes.h"
#include "headers/molecule/mmring.h"
#include "headers/statics/protpool.h"




IWtoMacro::IWtoMacro(const std::string &SMILES,
                     const std::string& name)
{
    if (!mIWMole.build_from_smiles(SMILES.c_str()))
        throw_line("250101",
                   "IWtoMacro",
                   "Unsucessfull creation of SMILES "+SMILES);
    mIWMole.reduce_to_largest_fragment();
    mIWMole.set_name(name);

//    set_global_aromaticity_type(Daylight);

//    set_kekule_try_positive_nitrogen(1);

//    set_convert_chain_aromatic_bonds(1);

//    set_invalidate_bond_list_ring_info_during_invalidate_ring_info(0);

//    mIWMole.compute_aromaticity();

//    mNRing=mIWMole.nrings();
}






void IWtoMacro::setMacroMole(protspace::MacroMole& mole){ mMacro=&mole; }



void IWtoMacro::createAtoms(protspace::MMResidue& pRes)
{
   protspace::CoordPoolObj coo;
   try{
        const int natom= mIWMole.natoms();
        for(int i=0;i<natom;++i)
        {
            const Atom& atom = mIWMole.atom(i);
            IWString smi=atom.atomic_symbol();
            coo.obj.setxyz((double)atom.x(),(double)atom.y(),(double)atom.z());
            protspace::MMAtom& atm=
                    mMacro->addAtom(pRes,
                                    coo.obj,
                                    smi.c_str(),
                                    "",
                                    smi.c_str());

            atom_mapping_to.insert(std::make_pair(&atm,&atom));
            atom_mapping.insert(   std::make_pair(&atom,&atm));
            mapping.insert(        std::make_pair(i,&atm));
        }

    }catch(ProtExcept &e)
    {
        assert(e.getId()!="350101");///Molecule alias
        assert(e.getId()!="310802"&&e.getId()!="350302");;///No MOL2 involved
        assert(e.getId()!="350301");///Residue MUST be in this molecule
        e.addHierarchy("IWtoMacro::createAtoms");
        throw;
    }
}






void IWtoMacro::createBonds()
{
    try{
        const int natom= mIWMole.natoms();
        for(int i=0;i<natom;++i)
        {
            const Atom& atom = mIWMole.atom(i);
            int acon = atom.ncon();
            for (int j = 0; j < acon; j++)
            {
                const Bond & bond = *atom.item (j);
                if (i >= bond.other (i))continue;
                uint32_t btype=BOND::UNDEFINED;
//                if (IS_AROMATIC_BOND(bond.btype())) btype = BOND::AROMATIC_BD;
//                else
                     if (bond.btype() == SINGLE_BOND) btype = BOND::SINGLE;
                else if (bond.btype() == DOUBLE_BOND) btype = BOND::DOUBLE;
                else if (bond.btype() == TRIPLE_BOND) btype = BOND::TRIPLE;
                auto it1= mapping.find(bond.a1());
                auto it2= mapping.find(bond.a2());
                if (it1 == mapping.end())
                {
                    cerr << bond<<endl;
                    continue;
                }
                if (it2 ==mapping.end())
                {
                    cerr <<bond<<endl;
                    continue;
                }
                mMacro->addBond(*mapping.at(bond.a1()),*mapping.at(bond.a2()),btype,mMacro->numBonds());

                // cout <<bond.a1()<<" " << bond.a2()<<endl;
            }
        }
    }catch(ProtExcept &e)
    {

        assert(e.getId()!="350601" && e.getId()!="350602"&&e.getId()!="350603");
        e.addHierarchy("IWtoMacro::createBonds");
        throw;
    }
}




void IWtoMacro::perceiveArom()
{
    set_global_aromaticity_type(Daylight);

    set_kekule_try_positive_nitrogen(1);

    set_convert_chain_aromatic_bonds(1);

    set_invalidate_bond_list_ring_info_during_invalidate_ring_info(0);

    mIWMole.compute_aromaticity();

    mNRing=mIWMole.nrings();
}

bool IWtoMacro::toMacroMole(const bool& wAromatic, protspace::MMResidue& pRes)
{
    if (mMacro == nullptr)return false;
    try{

        createAtoms(pRes);
        createBonds();



        if (!wAromatic)return true;
        perceiveArom();
        std::vector<protspace::MMAtom*> atomlist;

        for (size_t iRing=0; iRing<mNRing;++iRing)
        {
            const Ring & ring = *mIWMole.ringi(iRing);
            atomlist.clear();
            for (unsigned int nAtm=0;nAtm < ring.size();++nAtm)
            {
                atomlist.push_back(&mMacro->getAtom(ring[nAtm]));

            }mMacro->addRingSystem(atomlist,ring.is_aromatic());
        }
        return true;
    }catch(ProtExcept &e)
    {
        assert(e.getId()!="030401");/// Position out of bound shouldn't happen
        assert(e.getId()!="350101");/// Alias molecule
        e.addHierarchy("IWtoMacro::toMacroMole");
        throw;
    }
}




void IWtoMacro::generateRings()
try{
      perceiveArom();

    std::vector<protspace::MMAtom*> atomlist;
    const int nrings=  mIWMole.nrings ();
    mMacro->clearRing();
    for (int iRing=0; iRing<nrings;++iRing)
    {

        const Ring & ring = *mIWMole.ringi(iRing);
        atomlist.clear();

        for (unsigned int nAtm=0;nAtm < ring.size();++nAtm)
        {
            const  Atom& atm=mIWMole.atom(ring[nAtm]);
            protspace::MMAtom& mmatom=*atom_mapping.at(&atm);
            atomlist.push_back(&mmatom);
        }
        mMacro->addRingSystem(atomlist,ring.is_aromatic());
    }
}catch(ProtExcept &e)
{
    assert(e.getId()!="352601");/// Deleted Ring must be in molecule
    assert(e.getId()!="350101");/// Cannot be alias
    e.addHierarchy("IWtoMacro::generateRings()");
}




std::string IWtoMacro::getUniqueSMILES()
{
    IWString smi=mIWMole.unique_smiles();
    const std::string val(smi.c_str());
    return val;
}





namespace protspace
{
void SMILEStoMacroMole(const std::string& SMILES,
                       const std::string& name,
                       MacroMole& mole,
                       const bool& perceiveRing)
{
    try{
        IWtoMacro iwmr(SMILES,name);

        iwmr.setMacroMole(mole);
        iwmr.toMacroMole(true,mole.getTempResidue());
        mole.setName(name);
        if (perceiveRing) iwmr.generateRings();
    }catch(ProtExcept &e)
    {
        e.addHierarchy("SMILEStoMacroMole");
        e.addDescription("Input SMILES: "+SMILES);
        e.addDescription("Input Name : "+name);
        throw;
    }
}

}
