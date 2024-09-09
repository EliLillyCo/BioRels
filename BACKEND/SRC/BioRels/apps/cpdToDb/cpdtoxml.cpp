//
// Created by c188973 on 10/26/16.
//

#include <fstream>
#include <sstream>
#include <headers/statics/protExcept.h>
#include "headers/molecule/macromole_utils.h"
#include "headers/statics/protpool.h"
#include "cpdtoxml.h"
#include "headers/statics/intertypes.h"
#include "base64.h"
#include "headers/statics/logger.h"

CpdToXML::CpdToXML() {
    prepareHeader();
}

std::string CpdToXML::getTime()const
{
    time_t now=time(0);
    struct tm tstruct;
    char buf[80];
    tstruct = *localtime(&now);
    strftime(buf,sizeof(buf),"%m/%d/%G %R:%S",&tstruct);
    protspace::StringPoolObj str;str=buf;
    return str.get();
}



void CpdToXML::prepareHeader()
try{

    pugi::xml_node Message= mDocument.append_child("Message");

    // Creation of the header message of the xml document :
    pugi::xml_node Header=Message.append_child("Header");
    Header.append_attribute("creationTimestamp").set_value(getTime().c_str());
    Header.append_attribute("entity").set_value("residue");
    Header.append_attribute("transactionType").set_value("create");

    mWorkflows=Header.append_child("Workflows");
    // Creation of the body message of the xml document
    pugi::xml_node Body=Message.append_child("Body");
    mResidues=Body.append_child("Residues");

}catch(ProtExcept &e)
{
    e.addHierarchy("CpdToXML::prepareHeader");
    throw;
}



void CpdToXML::exportFile(const std::string &pFile) const{
    std::ofstream ofs(pFile);
    if (!ofs.is_open())
        throw_line("2310101",
                   "CpdToXML::exportFile",
                   "Unable to open file "+pFile);
    std::ostringstream content;
    mDocument.save(content);
    mDocument.print(ofs);
    ofs.close();
}



void CpdToXML::addResidueData(const Compound& pCompound,pugi::xml_node& residue)
{

    residue.append_child("ResidueName")
            .append_child(pugi::node_pcdata)
            .set_value(pCompound.getName().c_str());
    residue.append_child("TautomerId")
            .append_child(pugi::node_pcdata)
            .set_value("1");


    const protspace::StringPoolObj replaced_by(pCompound.getReplaced_By());
    if (replaced_by.get() != "")
        residue.append_child("ReplacedBy")
                .append_child(pugi::node_pcdata)
                .set_value(replaced_by.get().c_str());

    residue.append_child("IsLilly")
            .append_child(pugi::node_pcdata)
            .set_value((pCompound.isLilly())?"true":"false");

    pugi::xml_node resType=residue.append_child("Class").append_child(pugi::node_pcdata);


    resType.set_value(pCompound.getClass().c_str());
    pugi::xml_node resSubClass=residue.append_child("Subclass").append_child(pugi::node_pcdata);
    resSubClass.set_value("");

    residue.append_child("Smiles")
            .append_child(pugi::node_pcdata)
            .set_value(pCompound.getSMILES().c_str());
}


void CpdToXML::addMoleculeData(const Compound& pCompound,pugi::xml_node& residue)
{
    const protspace::MacroMole& mole= pCompound.getMole();


    pugi::xml_node attributes = residue.append_child("Attributes");

    pugi::xml_node mweight=attributes.append_child("Attribute");
    mweight.append_attribute("attributeName").set_value("Molecular_Weight");
    if (pCompound.hasProp("Molecular_weight")) {
        mweight.append_attribute("attributeValue")
                .set_value(pCompound.hasProp("Molecular_weight"));
    }
    else
    {
        mweight.append_attribute("attributeValue")
                .set_value(protspace::getMolecularWeigth(mole));
    }


    pugi::xml_node fname =attributes.append_child("Attribute");
    fname.append_attribute("attributeName").set_value("Fullname");
    fname.append_attribute("attributeValue").set_value(pCompound.getName().c_str());

    if (pCompound.hasProp("formula"))
    {
        pugi::xml_node form =attributes.append_child("Attribute");
        form.append_attribute("attributeName").set_value("Formula");
        form.append_attribute("attributeValue").set_value(pCompound.getProp("formula").c_str());
    }
}

void CpdToXML::addAtomData(const Compound& pCompound,pugi::xml_node& residue)
{
    const protspace::MacroMole& mole= pCompound.getMole();
    pugi::xml_node atoms=residue.append_child("Atoms");
    for (size_t iAtm=0; iAtm < mole.numAtoms();++iAtm)
    {
        const protspace::MMAtom& atom=mole.getAtom(iAtm);
        if (atom.getName()=="DuCy"||atom.getName()=="DuAr")continue;
        pugi::xml_node atom_x=atoms.append_child("Atom");
        atom_x.append_attribute("elementSymbol").set_value(atom.getElement().c_str());
        atom_x.append_attribute("mol2Type").set_value(atom.getMOL2().c_str());
        atom_x.append_attribute("mappingIdentifier").set_value(atom.getMID());
        atom_x.append_attribute("atomName").set_value(atom.getName().c_str());

    }
}


void CpdToXML::addBondData(const Compound& pCompound,pugi::xml_node& residue)
{
    const protspace::MacroMole& mole= pCompound.getMole();
    pugi::xml_node bonds=residue.append_child("Bonds");

    for (size_t iBond=0; iBond < mole.numBonds();++iBond)
    {
        protspace::MMBond& bond=mole.getBond(iBond);
        pugi::xml_node bond_x=bonds.append_child("Bond");
        bond_x.append_attribute("atom1").set_value(bond.getAtom1().getMID());
        bond_x.append_attribute("atom2").set_value(bond.getAtom2().getMID());
        bond_x.append_attribute("bondType").set_value(BOND::typeToMOL2.at(bond.getType()).c_str());

    }

}




void CpdToXML::processEntry(const Compound &pCompound) {
    try{
        pugi::xml_node xmlResidues=mDocument.child("Message")
                .child("Body")
                .child("Residues");

        pugi::xml_node residue=xmlResidues.append_child("Residue");
        addResidueData(pCompound,residue);
        addMoleculeData(pCompound,residue);
        addAtomData(pCompound,residue);
        addBondData(pCompound,residue);

    }catch(ProtExcept &e)
    {
        e.addHierarchy("CpdToXML::processEntry");
        throw;
    }
}


